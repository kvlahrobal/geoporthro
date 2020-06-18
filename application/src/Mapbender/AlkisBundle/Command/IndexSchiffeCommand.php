<?php
namespace Mapbender\AlkisBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Mapbender\AlkisBundle\Component\ColognePhonetic;

use ARP\SolrClient2\SolrClient;

class IndexSchiffeCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setDescription('Reset\'s the solr index.')
        ->setName('hro:index:reset:schiffe');
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

        
        $type = 'schiffe';
        $phonetic = ColognePhonetic::singleton();
        
        $solr = new SolrClient(
            $this->getContainer()->getParameter('solr')
        );

        $solr->deleteByQuery('id:' . $type . '_' . '*');
        $solr->commit();

        $conn = $this->getContainer()->get('doctrine.dbal.hro_search_data_connection');

        $limit = 50;
        $offset = 0;
        $id = 0;

        $output->writeln('Indiziere Schiffe fuer Schiffssuche Hanse Sail ... ');


        $stmt = $conn->query('SELECT count(*) AS count FROM fachdaten.hansesail_schiffe_schiffsliegeplaetze_regis');
        $result = $stmt->fetch();
        $count = intval($result['count']);

        while ($offset < $count) {
            $stmt = $conn->query("
                SELECT
                 schiff_bezeichnung AS bezeichnung,
                 schiff_typ AS typ,
                 schiff_baujahr AS baujahr,
                 schiffsliegeplatz_bezeichnung_kurz AS liegeplatz_bezeichnung_kurz,
                 schiffsliegeplatz_bezeichnung_lang AS liegeplatz_bezeichnung_lang,
                 ST_AsText(ST_Centroid(geometrie)) AS geom,
                 ST_AsText(geometrie) AS wktgeom
                  FROM fachdaten.hansesail_schiffe_schiffsliegeplaetze_regis
                   ORDER BY uuid
                    LIMIT " . $limit . " OFFSET " . $offset);

            while ($row = $stmt->fetch()) {
                list($x, $y) = $this->prepairPoint($row['geom']);

                $doc = $solr->newDocument();
                $doc->id = $type . '_' . ++$id;
                $doc->text = $this->concat(
                    $row['bezeichnung'],
                    $row['typ'],
                    $row['baujahr'],
                    $row['liegeplatz_bezeichnung_kurz'],
                    $row['liegeplatz_bezeichnung_lang']
                );
                
                $doc->phonetic = $this->addPhonetic($this->concat(
                    $row['bezeichnung'],
                    $row['typ'],
                    $row['baujahr'],
                    $row['liegeplatz_bezeichnung_kurz'],
                    $row['liegeplatz_bezeichnung_lang']
                ));

                $doc->label = "1".$row['bezeichnung'].$row['typ'].$row['baujahr'].$row['liegeplatz_bezeichnung_kurz'].$row['liegeplatz_bezeichnung_lang'];

                $doc->json = json_encode(array(
                    'data'   => array(
                        'type'                          => $type,
                        'bezeichnung'                   => $row['bezeichnung'],
                        'typ'                           => $row['typ'],
                        'baujahr'                       => $row['baujahr'],
                        'liegeplatz_bezeichnung_kurz'   => $row['liegeplatz_bezeichnung_kurz'],
                        'liegeplatz_bezeichnung_lang'   => $row['liegeplatz_bezeichnung_lang']
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
                $offset > $count ? $count : $offset
            ) . " von " . $count . " indiziert.");
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
