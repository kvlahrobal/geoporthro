<?php
namespace Mapbender\AlkisBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
//use Mapbender\AlkisBundle\Component\ColognePhonetic;

use ARP\SolrClient2\SolrClient;

class IndexGemeindeMvCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setDescription('Reset\'s the solr index.')
        ->setName('hro:index:reset:mv_gemeinden');
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


        $type = 'mv_gemeinde';

        $solr = new SolrClient(
            $this->getContainer()->getParameter('solr')
        );

        $solr->deleteByQuery('id:' . $type . '_' . '*');
        $solr->commit();

        $conn = $this->getContainer()->get('doctrine.dbal.hro_search_data_connection');

        $limit = 50;
        $offset = 0;
        $id = 0;

        $output->writeln('Indiziere Gemeinden fuer MV-Adressensuche ... ');

        $stmt = $conn->query("SELECT count(*) AS count FROM regis.gemeinden");
        $result = $stmt->fetch();

        while ($offset < $result['count']) {
            $stmt = $conn->query("
                SELECT regexp_replace(gemeinde_name, '\, .*$', '') AS gemeinde_name,
                ST_AsText(ST_Centroid(geometrie)) AS geom,
                ST_AsText(geometrie) AS wktgeom
                FROM regis.gemeinden ORDER BY id LIMIT " . $limit . " OFFSET " . $offset);

            while ($row = $stmt->fetch()) {
                list($x, $y) = $this->prepairPoint($row['geom']);

                $doc = $solr->newDocument();
                $doc->id = $type . '_' . ++$id;
                $doc->text = $row['gemeinde_name'];

                //$doc->phonetic = $this->addPhonetic( $row['gemeinde_name']);

                $doc->label = "1";

                $doc->json = json_encode(array(
                    'data' => array(
                        'type'                  => $type,
                        'gemeinde_name'         => $row['gemeinde_name']
                    ),
                    'x'    => $x,
                    'y'    => $y,
                    'geom' => $row['wktgeom']
                ));
                $doc->type = 'mv_addr';
                $solr->appendDocument($doc);
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

    /*public function addPhonetic($string)
    {
        $phonetic = ColognePhonetic::singleton();
        $phoneticArray = array();

        $words = array_filter(
            explode(" ", preg_replace("/[^a-zäöüßÄÖÜ0-9]/i", " ", $string))
        );

        foreach ($words as $word) {
            if (preg_match("/^[a-zäöüßÄÖÜ]+$/i", $word)) {
                $phoneticArray[] = $phonetic->encode($word);
            }
        }

        return $phoneticArray;
    }*/
}
