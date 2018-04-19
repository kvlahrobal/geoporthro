<?php

namespace Mapbender\AlkisBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use ARP\SolrClient2\SolrClient;
use Mapbender\AlkisBundle\Component\Flurstueck;

class IndexFlurMvCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setDescription('Reset\'s the solr index.')
        ->setName('hro:index:reset:mv_fluren');
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

        $type = 'mv_flur';
        $solr = new SolrClient(
            $this->getContainer()->getParameter('solr')
        );

        $solr->deleteByQuery('id:' . $type . '_' . '*');
        $solr->commit();

        $conn = $this->getContainer()->get('doctrine.dbal.hro_search_data_connection');

        $id = 0;
        $limit = 1000;
        $offset = 0;

        $output->writeln('Indiziere Fluren fuer MV-Flurstueckssuche ... ');

        $stmt = $conn->query('SELECT count(*) AS count FROM regis.fluren_alle');
        $result = $stmt->fetch();

        while ($offset < $result['count']) {
            $stmt = $conn->query("
                SELECT suche_gemeinde_name AS gemeinde_name,
                gemarkung_name,
                gemarkung_schluessel,
                suche_gemarkung_schluessel AS gemarkung_schluessel_kurz,
                flur::int AS flur_kurz,
                suche_geom AS geom,
                suche_wktgeom AS wktgeom
                FROM regis.fluren_alle ORDER BY id LIMIT " . $limit . " OFFSET " . $offset);

            while ($row = $stmt->fetch()) {
                list($x, $y) = $this->prepairPoint($row['geom']);

                $doc = $solr->newDocument();
                $doc->id = $type . '_' . ++$id;
                $doc->text = $row['flur_kurz'] . ' ' .
                    $row['gemarkung_schluessel_kurz'] . ' ' .
                    $row['gemarkung_schluessel'] . ' ' .
                    $row['gemarkung_name'];

                $doc->label = str_pad($row['flur_kurz'], 17, '0', STR_PAD_LEFT);

                $doc->json = json_encode(array(
                    'data'   => array(
                        'type'                      => $type,
                        'gemeinde_name'             => $row['gemeinde_name'],
                        'gemarkung_name'            => $row['gemarkung_name'],
                        'gemarkung_schluessel'      => $row['gemarkung_schluessel'],
                        'gemarkung_schluessel_kurz' => $row['gemarkung_schluessel_kurz'],
                        'flur'                      => $row['flur_kurz']
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

    public function prepairPoint($p)
    {
        if (substr($p, 0, 5) === 'POINT') {
            return explode(' ', substr($p, 6, -1));
        }

        return array('','');
    }
}
