<?php

namespace Mapbender\AlkisBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use ARP\SolrClient2\SolrClient;
use Mapbender\AlkisBundle\Component\Flurstueck;

class IndexGemarkungMvCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setDescription('Reset\'s the solr index.')
        ->setName('hro:index:reset:mv_gemarkungen');
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

        $type = 'mv_gemarkung';
        $solr = new SolrClient(
            $this->getContainer()->getParameter('solr')
        );

        $solr->deleteByQuery('id:' . $type . '_' . '*');
        $solr->commit();

        $conn = $this->getContainer()->get('doctrine.dbal.hro_search_data_connection');

        $id = 0;
        $limit = 1000;
        $offset = 0;

        $output->writeln('Indiziere Gemarkungen fuer MV-Flurstueckssuche ... ');


        $stmt = $conn->query('SELECT count(*) AS count FROM regis.gemarkungen_alle');
        $result = $stmt->fetch();

        while ($offset < $result['count']) {
            $stmt = $conn->query("
                SELECT suche_gemeinde_name AS gemeinde_name,
                gemarkung_name,
                gemarkung_schluessel,
                suche_gemarkung_schluessel AS gemarkung_schluessel_kurz,
                suche_geom AS geom,
                suche_wktgeom AS wktgeom
                FROM regis.gemarkungen_alle ORDER BY id LIMIT " . $limit . " OFFSET " . $offset);

            while ($row = $stmt->fetch()) {
                list($x, $y) = $this->prepairPoint($row['geom']);

                $doc = $solr->newDocument();
                $doc->id = $type . '_' . ++$id;
                $doc->text = $row['gemarkung_name'] . ' ' .
                    $row['gemarkung_schluessel_kurz'] . ' ' .
                    $row['gemarkung_schluessel'];

                $doc->label = '000000000000000000';
                
                $doc->json = json_encode(array(
                    'data'   => array(
                        'type'                      => $type,
                        'gemeinde_name'             => $row['gemeinde_name'],
                        'gemarkung_name'            => $row['gemarkung_name'],
                        'gemarkung_schluessel'      => $row['gemarkung_schluessel'],
                        'gemarkung_schluessel_kurz' => $row['gemarkung_schluessel_kurz']
                        
                    ),
                    'x'      => $x,
                    'y'      => $y,
                    'geom'   => $row['wktgeom'],
                ));
                $doc->type = 'mv_flur';

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

    public function prepairPoint($p)
    {
        if (substr($p, 0, 5) === 'POINT') {
            return explode(' ', substr($p, 6, -1));
        }

        return array('','');
    }
}
