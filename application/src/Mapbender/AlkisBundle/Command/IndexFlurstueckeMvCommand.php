<?php

namespace Mapbender\AlkisBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use ARP\SolrClient2\SolrClient;
use Mapbender\AlkisBundle\Component\Flurstueck;

class IndexFlurstueckeMvCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setDescription('Reset\'s the solr index.')
        ->setName('hro:index:reset:mv_flurstuecke');
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

        $type = 'mv_flurstueck';
        $solr = new SolrClient(
            $this->getContainer()->getParameter('solr')
        );

        $solr->deleteByQuery('id:' . $type . '_' . '*');
        $solr->commit();

        $conn = $this->getContainer()->get('doctrine.dbal.hro_search_data_connection');

        // niedriges "Pagination"-Limit, da sonst Abfragen nicht vollständig durchlaufen und damit nicht alle Objekte aus der DB-Tabelle in den Index gelangen
        $limit = 200;
        $offset = 0;
        $id = 0;

        $output->writeln('Indiziere Flurstuecke fuer MV-Flurstueckssuche ... ');

        $stmt = $conn->query('SELECT count(*) AS count FROM regis.flurstuecke_alle');
        $result = $stmt->fetch();

        while ($offset < $result['count']) {
            $stmt = $conn->query("
                SELECT suche_gemeinde_name AS gemeinde_name,
                gemarkung_name,
                gemarkung_schluessel,
                '13' AS land_schluessel,
                suche_gemarkung_schluessel AS gemarkung_schluessel_kurz,
                flur::int AS flur_kurz,
                zaehler::int AS zaehler_kurz,
                nenner::int AS nenner_kurz,
                suche_geom AS geom,
                suche_wktgeom AS wktgeom,
                suche_flurstueckskennzeichen AS flurstueckskennzeichen
                FROM regis.flurstuecke_alle ORDER BY id LIMIT " . $limit . " OFFSET " . $offset);

            while ($row = $stmt->fetch()) {
                $flurstueck = new Flurstueck(
                    $row['land_schluessel'],
                    $row['gemarkung_schluessel_kurz'],
                    $row['flur_kurz'],
                    $row['zaehler_kurz'],
                    $row['nenner_kurz']
                );

                list($x, $y) = $this->prepairPoint($row['geom']);

                $doc = $solr->newDocument();
                $doc->id = $type . '_' . ++$id;
                $doc->text =
                    $row['nenner_kurz'] . ' ' .
                    $row['zaehler_kurz'] . ' ' .
                    $row['flur_kurz'] . ' ' .
                    $row['gemarkung_schluessel_kurz'] . ' ' .
                    $row['gemarkung_schluessel'] . ' ' .
                    $row['gemarkung_name'] . ' ' .
                    $row['gemeinde_name'] . ' ' .
                    $row['flurstueckskennzeichen'];

                $doc->label = $flurstueck->getNumber();

                $doc->json = json_encode(array(
                    'data'   => array(
                        'type'                      => $type,
                        'gemeinde_name'             => $row['gemeinde_name'],
                        'gemarkung_name'            => $row['gemarkung_name'],
                        'gemarkung_schluessel'      => $row['gemarkung_schluessel'],
                        'gemarkung_schluessel_kurz' => $row['gemarkung_schluessel_kurz'],
                        'flur'                      => $row['flur_kurz'],
                        'zaehler'                   => $row['zaehler_kurz'],
                        'nenner'                    => $row['nenner_kurz']
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

            $output->writeln("\t$offset von " . $result['count'] . " indiziert.");
        }

        $solr->commit();
        $solr->optimize();

        $output->writeln('fertig');
    }

    public function tokenize($string)
    {
        return implode(
            " ",
            array_filter(
                explode(" ", preg_replace("/\\W/", " ", $string))
            )
        );
    }

    public function concat()
    {
        return implode(" ", array_filter(func_get_args()));
    }

    public function prepairFlurstueckskennzeichen($f)
    {
        return
            '(' . substr($f, 0, 2) . ') '
            . substr($f, 2, 4) . '-'
            . substr($f, 6, 3) . '-'
            . substr($f, 9, 5) . (substr($f, 14) != "" ? '/' : '')
            . substr($f, 14);
    }

    public function prepairPoint($p)
    {
        if (substr($p, 0, 5) === 'POINT') {
            return explode(' ', substr($p, 6, -1));
        }

        return array('','');
    }
}
