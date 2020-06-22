<?php
namespace Mapbender\AlkisBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Mapbender\AlkisBundle\Component\ColognePhonetic;

use ARP\SolrClient2\SolrClient;

class IndexGruenpflegeobjekteCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setDescription('Reset\'s the solr index.')
        ->setName('hro:index:reset:gruenpflegeobjekte');
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

        
        $type = 'gruenpflegeobjekte';
        $phonetic = ColognePhonetic::singleton();
        
        $solr = new SolrClient(
            $this->getContainer()->getParameter('solr')
        );

        $solr->deleteByQuery('id:' . $type . '_' . '*');
        $solr->commit();

        $conn = $this->getContainer()->get('doctrine.dbal.hro_search_data_connection');

        $limit = 100;
        $offset = 0;
        $id = 0;

        $output->writeln('Indiziere Gruenpflegeobjekte fuer HRO-Gruenpflegeobjektesuche ... ');


        $stmt = $conn->query("SELECT count(*) AS count FROM fachdaten.gruenpflegeobjekte_regis_hro WHERE rubrik IN ('Friedhof', 'Park oder Grünanlage', 'Spielplatz', 'Straßenbegleitgrün')");
        $result = $stmt->fetch();

        while ($offset < $result['count']) {
            $stmt = $conn->query("
                SELECT
                 gruenpflegebezirk AS bezirk,
                 nummer AS objektnummer,
                 regexp_replace(nummer, '\/', '', 'g') AS objektnummer_ohne_slashes,
                 regexp_replace(nummer, '\/', ' ', 'g') AS objektnummer_mit_leerzeichen,
                 bezeichnung AS objektbezeichnung,
                 art AS objektart,
                 ST_AsText(geometrie) AS wktgeom
                  FROM fachdaten.gruenpflegeobjekte_regis_hro
                   WHERE rubrik IN ('Friedhof', 'Park oder Grünanlage', 'Spielplatz', 'Straßenbegleitgrün')
                    ORDER BY uuid
                     LIMIT " . $limit . " OFFSET " . $offset);

            while ($row = $stmt->fetch()) {

                $doc = $solr->newDocument();
                $doc->id = $type . '_' . ++$id;
                $doc->text = $this->concat(
                    $row['bezirk'],
                    $row['objektnummer'],
                    $row['objektnummer_ohne_slashes'],
                    $row['objektnummer_mit_leerzeichen'],
                    $row['objektbezeichnung'],
                    $row['objektart']
                );
                
                $doc->phonetic = $this->addPhonetic($this->concat(
                    $row['bezirk'],
                    $row['objektnummer'],
                    $row['objektnummer_ohne_slashes'],
                    $row['objektnummer_mit_leerzeichen'],
                    $row['objektbezeichnung'],
                    $row['objektart']
                ));

                $doc->label = "1".$row['bezirk'].$row['objektnummer'].$row['objektnummer_ohne_slashes'].$row['objektnummer_mit_leerzeichen'].$row['objektbezeichnung'].$row['objektart'];

                $doc->json = json_encode(array(
                    'data'   => array(
                        'type'              => $type,
                        'bezirk'            => $row['bezirk'],
                        'objektnummer'      => $row['objektnummer'],
                        'objektbezeichnung' => $row['objektbezeichnung'],
                        'objektart'         => $row['objektart']
                    ),
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
