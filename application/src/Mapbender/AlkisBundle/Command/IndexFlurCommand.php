<?php

namespace Mapbender\AlkisBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use ARP\SolrClient2\SolrClient;
use Mapbender\AlkisBundle\Component\Flurstueck;

class IndexFlurCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setDescription('Reset\'s the solr index.')
        ->setName('hro:index:reset:fluren');
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

        $type = 'flur';
        $solr = new SolrClient(
            $this->getContainer()->getParameter('solr')
        );

        $solr->deleteByQuery('id:' . $type . '_' . '*');
        $solr->commit();

        $conn = $this->getContainer()->get('doctrine.dbal.hro_data_connection');

        $id = 0;
        $limit = 10;
        $offset = 0;

        $output->writeln('Indiziere Fluren fuer HRO-Flurstueckssuche ... ');

        $stmt = $conn->query('SELECT count(*) AS count FROM prozessiert.flur');
        $result = $stmt->fetch();

        while ($offset < $result['count']) {
            $stmt = $conn->query('
                SELECT f.ogc_fid,
                g.gemarkungsname,
                g.gemarkungsnummer,
                substring(g.gemarkungsnummer from 3) AS gemarkung,
                f.flurnummer,
                ST_AsText(ST_Centroid(f.wkb_geometry)) AS geom,
                ST_AsText(ST_Transform(f.wkb_geometry, 4326)) as wkt,
                ST_AsText(f.wkb_geometry) AS wktgeom
                FROM prozessiert.flur f
                JOIN prozessiert.gemarkung g ON f.gemarkungsnummer = g.gemarkungsnummer
                ORDER BY f.ogc_fid LIMIT ' . $limit . ' OFFSET ' . $offset);

            while ($row = $stmt->fetch()) {
                list($x, $y) = $this->prepairPoint($row['geom']);

                $doc = $solr->newDocument();
                $doc->id = $type . '_' . ++$id;
                $doc->text =
                    $row['flurnummer'] . ' ' .
                    $row['gemarkung'] . ' ' .
                    $row['gemarkungsnummer'] . ' ' .
                    $row['gemarkungsname'];

                $doc->label = str_pad($row['flurnummer'], 17, '0', STR_PAD_LEFT);
                $doc->geom = $row['wkt'];

                $doc->json = json_encode(array(
                    'data'   => array(
                        'type'                      => $type,
                        'gemarkung_name'            => $row['gemarkungsname'],
                        'gemarkung_schluessel_kurz' => $row['gemarkung'],
                        'flur'                      => $row['flurnummer']
                    ),
                    'x'      => $x,
                    'y'      => $y,
                    'geom'   => @$row['wktgeom'],
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
