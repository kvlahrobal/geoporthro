<?php
namespace Mapbender\AlkisBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Mapbender\AlkisBundle\Component\ColognePhonetic;

use ARP\SolrClient2\SolrClient;

class IndexBebauungsplaeneNurRkCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setDescription('Reset\'s the solr index.')
        ->setName('hro:index:reset:bebauungsplaene_nur_rk');
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

        
        $type = 'bebauungsplaene_nur_rk';
        $phonetic = ColognePhonetic::singleton();
        
        $solr = new SolrClient(
            $this->getContainer()->getParameter('solr')
        );

        $solr->deleteByQuery('id:' . $type . '_' . '*');
        $solr->commit();

        $conn = $this->getContainer()->get('doctrine.dbal.hro_search_data_connection');

        $limit = 10;
        $offset = 0;
        $id = 0;

        $output->writeln('Indiziere Bebauungsplaene (nur rechtskraeftig) fuer HRO-Bebauungsplaenesuche (nur rechtskraeftig) ... ');


        $stmt = $conn->query('SELECT count(*) AS count FROM fachdaten.bebauungsplaene_rechtskraeftig_geltungsbereiche_regis_hro');
        $result = $stmt->fetch();

        while ($offset < $result['count']) {
            $stmt = $conn->query("
                SELECT
                 nummer,
                 regexp_replace(nummer, '\.', '', 'g') AS nummer_ohne_punkte,
                 regexp_replace(nummer, '\.', ' ', 'g') AS nummer_mit_leerzeichen,
                 bezeichnung,
                 ST_AsText(ST_Centroid(geometrie)) AS geom,
                 ST_AsText(geometrie) AS wktgeom
                  FROM fachdaten.bebauungsplaene_rechtskraeftig_geltungsbereiche_regis_hro
                   ORDER BY uuid
                    LIMIT " . $limit . " OFFSET " . $offset);

            while ($row = $stmt->fetch()) {
                list($x, $y) = $this->prepairPoint($row['geom']);

                $doc = $solr->newDocument();
                $doc->id = $type . '_' . ++$id;
                $doc->text = $this->concat(
                    $row['nummer'],
                    $row['nummer_ohne_punkte'],
                    $row['nummer_mit_leerzeichen'],
                    $row['bezeichnung']
                );
                
                $doc->phonetic = $this->addPhonetic($this->concat(
                    $row['nummer'],
                    $row['nummer_ohne_punkte'],
                    $row['nummer_mit_leerzeichen'],
                    $row['bezeichnung']
                ));

                $doc->label = "1".$row['nummer'].$row['nummer_ohne_punkte'].$row['nummer_mit_leerzeichen'].$row['bezeichnung'];

                $doc->json = json_encode(array(
                    'data'   => array(
                        'type'              => $type,
                        'nummer'            => $row['nummer'],
                        'bezeichnung'       => $row['bezeichnung']
                    ),
                    'x'      => $x,
                    'y'      => $y,
                    'geom'   => $row['wktgeom'],
                ));
                $doc->type = $type;

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
