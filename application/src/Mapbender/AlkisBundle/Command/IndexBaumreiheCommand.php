<?php
namespace Mapbender\AlkisBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Mapbender\AlkisBundle\Component\ColognePhonetic;

use ARP\SolrClient2\SolrClient;

class IndexBaumreiheCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setDescription('Reset\'s the solr index.')
        ->setName('hro:index:reset:baumreihen');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        error_reporting(E_ERROR);

        // Force garbage collector to do its job.
        gc_collect_cycles();

        // Turn SQL-Logger off.
        $this
            ->getContainer()
            ->get('doctrine')
            ->getManager()
            ->getConnection()
            ->getConfiguration()
            ->setSQLLogger(null);

        
        $type = 'baumreihe';
        $phonetic = ColognePhonetic::singleton();
        
        $solr = new SolrClient(
            $this->getContainer()->getParameter('solr')
        );

        $solr->deleteByQuery('id:' . $type . '_' . '*');
        $solr->commit();

        $conn = $this->getContainer()->get('doctrine.dbal.hro_search_data_connection');

        $limit = 80;
        $offset = 0;
        $id = 0;

        $output->writeln('Indiziere Baumreihen fuer HRO-Baumkatastersuche ... ');


        $stmt = $conn->query('SELECT count(*) AS count FROM fachdaten.baumreihen_regis_hro');
        $result = $stmt->fetch();

        while ($offset < $result['count']) {
            $stmt = $conn->query("
               SELECT
                 gruenpflegebezirk AS bezirk,
                 nummer_gruenpflegeobjekt AS objektnummer,
                 regexp_replace(nummer_gruenpflegeobjekt, '\/', '', 'g') AS objektnummer_ohne_slashes,
                 regexp_replace(nummer_gruenpflegeobjekt, '\/', ' ', 'g') AS objektnummer_mit_leerzeichen,
                 bezeichnung_gruenpflegeobjekt AS objektbezeichnung,
                 teilnummer AS teil,
                 ST_AsText(ST_Centroid(geometrie)) AS geom,
                 ST_AsText(geometrie) AS wktgeom
                  FROM fachdaten.baumreihen_regis_hro
                   ORDER BY uuid
                    LIMIT " . $limit . " OFFSET " . $offset);

            while ($row = $stmt->fetch()) {
                list($x, $y) = $this->prepairPoint($row['geom']);

                $doc = $solr->newDocument();
                $doc->id = $type . '_' . ++$id;
                $doc->text = $this->concat(
                    $row['bezirk'],
                    $row['objektnummer'],
                    $row['objektnummer_ohne_slashes'],
                    $row['objektnummer_mit_leerzeichen'],
                    $row['objektbezeichnung'],
                    $row['teil']
                );
                
                $doc->phonetic = $this->addPhonetic($this->concat(
                    $row['bezirk'],
                    $row['objektnummer'],
                    $row['objektnummer_ohne_slashes'],
                    $row['objektnummer_mit_leerzeichen'],
                    $row['objektbezeichnung'],
                    $row['teil']
                ));

                $doc->label = "2".$row['bezirk'].$row['objektnummer'].$row['objektnummer_ohne_slashes'].$row['objektnummer_mit_leerzeichen'].$row['objektbezeichnung'].$row['teil'];

                $doc->json = json_encode(array(
                    'data'   => array(
                        'type'              => 'baumreihe',
                        'bezirk'            => $row['bezirk'],
                        'objektnummer'      => $row['objektnummer'],
                        'objektbezeichnung' => $row['objektbezeichnung'],
                        'teil'              => $row['teil']
                    ),
                    'x'      => $x,
                    'y'      => $y,
                    'geom'   => $row['wktgeom'],
                ));
                $doc->type = 'baumkataster';

                $solr->addDocument($doc);
            }

            $solr->commit();
            $offset += $limit;

            $output->writeln("\t" . (
                $offset > $result['count'] ? $result['count'] : $offset
            ) . " von " . $result['count'] . " indiziert.");
        }

        $solr->commit();
        $solr->optimize();

        $output->writeln('fertig');
    }

    public function concat()
    {
        return implode(" ", array_filter(func_get_args()));
    }

    public function prepairPoint($p)
    {
        if (substr($p, 0, 5) === 'POINT') {
            return explode(' ', substr($p, 6, -1));
        }

        return array('','');
    }

    public function addPhonetic($string)
    {
        $result   = "";
        $phonetic = ColognePhonetic::singleton();

        $array = array_filter(
            explode(" ", preg_replace("/[^a-zäöüßÄÖÜ0-9]/i", " ", $string))
        );

        foreach ($array as $val) {
            if (preg_match("/^[a-zäöüßÄÖÜ]+$/i", $val)) {
                $result .= " AND (" . $val. '^20 OR ' . $val . '*^15';
                
                if($val !== 'h') {
                    $result .= ' OR phonetic:' . $phonetic->encode($val) . '^1'
                    . ' OR phonetic:' . $phonetic->encode($val) . '*^0.5';
                }

                $result .= ")";
            } else {
                $result .= " AND (" . $val. '^2' . " OR " . $val . "*^1)";
            }
        }

        return substr(trim($result), 3);
    }
}
