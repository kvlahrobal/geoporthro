<?php

namespace Mapbender\AlkisBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use ARP\SolrClient2\SolrClient;
use Mapbender\AlkisBundle\Component\Flurstueck;

class IndexGemarkungCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setDescription('Reset\'s the solr index.')
        ->setName('hro:index:reset:gemarkungen');
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

        $type = 'gemarkung';
        $solr = new SolrClient(
            $this->getContainer()->getParameter('solr')
        );

        $solr->deleteByQuery('id:' . $type . '_' . '*');
        $solr->commit();

        $conn = $this->getContainer()->get('doctrine.dbal.hro_data_connection');

        $id = 0;
        $limit = 10;
        $offset = 0;

        $output->writeln('Indiziere Gemarkungen fuer HRO-Flurstueckssuche ... ');


        $stmt = $conn->query('SELECT count(*) AS count FROM prozessiert.gemarkung');
        $result = $stmt->fetch();

        while ($offset < $result['count']) {
            $stmt = $conn->query('
                SELECT ogc_fid,
                gemarkungsname,
                gemarkungsnummer,
                substring(gemarkungsnummer from 3) AS gemarkung,
                ST_AsText(ST_Centroid(wkb_geometry)) AS geom,
                ST_AsText(ST_Transform(wkb_geometry, 4326)) as wkt,
                ST_AsText(wkb_geometry) AS wktgeom
                FROM prozessiert.gemarkung ORDER BY ogc_fid LIMIT ' . $limit . ' OFFSET ' . $offset);

            while ($row = $stmt->fetch()) {
                list($x, $y) = $this->prepairPoint($row['geom']);

                $doc = $solr->newDocument();
                $doc->id = $type . '_' . ++$id;
                $doc->text = $row['gemarkungsname'] . ' ' .
                    $row['gemarkung'] . ' ' .
                    $row['gemarkungsnummer'];
                $doc->label = '000000000000000000';
                $doc->geom = $row['wkt'];

                $doc->json = json_encode(array(
                    'data'   => array(
                        'type'                      => $type,
                        'gemarkung_name'            => $row['gemarkungsname'],
                        'gemarkung_schluessel_kurz' => $row['gemarkung']
                    ),
                    'x'      => $x,
                    'y'      => $y,
                    'geom'   => @$row['wktgeom'],
                ));
                $doc->type = 'flur';

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
