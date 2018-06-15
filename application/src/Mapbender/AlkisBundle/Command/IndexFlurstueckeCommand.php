<?php

namespace Mapbender\AlkisBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use ARP\SolrClient2\SolrClient;
use Mapbender\AlkisBundle\Component\Flurstueck;

class IndexFlurstueckeCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setDescription('Reset\'s the solr index.')
        ->setName('hro:index:reset:flurstuecke');
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

        $type = 'flurstueck';
        $solr = new SolrClient(
            $this->getContainer()->getParameter('solr')
        );

        $solr->deleteByQuery('id:' . $type . '_' . '*');
        $solr->commit();

        $conn = $this->getContainer()->get('doctrine.dbal.hro_data_connection');

        // niedriges "Pagination"-Limit, da sonst Abfragen nicht vollständig durchlaufen und damit nicht alle Objekte aus der DB-Tabelle in den Index gelangen
        $limit = 1000;
        $offset = 0;
        $id = 0;

        $output->writeln('Indiziere Flurstuecke fuer HRO-Flurstueckssuche ... ');


        $stmt = $conn->query('SELECT count(*) AS count FROM prozessiert.flurstueck_flurstueckssuche');
        $result = $stmt->fetch();

        while ($offset < $result['count']) {
            $stmt = $conn->query("
            SELECT
             gml_id,
             historisch,
             historisch_seit,
             flurstueckskennzeichen,
             land,
             gemarkungsnummer,
             flurnummer,
             zaehler,
             nenner,
             gemarkungsname,
             gemeindename,
             geom,
             wktgeom,
             wkt
              FROM prozessiert.flurstueck_flurstueckssuche
               ORDER BY historisch DESC, historisch_seit, ogc_fid
                LIMIT " . $limit . " OFFSET " . $offset);

            while ($row = $stmt->fetch()) {
                $flurstueck = new Flurstueck(
                    $row['land'],
                    $row['gemarkungsnummer'],
                    $row['flurnummer'],
                    $row['zaehler'],
                    $row['nenner']
                );

                list($x, $y) = $this->prepairPoint($row['geom']);

                $doc = $solr->newDocument();
                $doc->id = $type . '_' . ++$id;
                $doc->text =
                    $row['nenner'] . ' ' .
                    $row['zaehler'] . ' ' .
                    $row['flurnummer'] . ' ' .
                    $row['gemarkungsnummer'] . ' ' .
                    $row['land'].$row['gemarkungsnummer'] . ' ' .
                    $row['gemarkungsname'] . ' ' .
                    $row['flurstueckskennzeichen'];

                $doc->label = $flurstueck->getNumber();
                $doc->geom = $row['wkt'];

                $doc->json = json_encode(array(
                    'data'   => array(
                        'type'                      => $type,
                        'historisch'                => $row['historisch'],
                        'historisch_seit'           => $row['historisch_seit'],
                        'land'                      => $row['land'],
                        'gemarkung_schluessel_kurz' => $row['gemarkungsnummer'],
                        'flur'                      => $row['flurnummer'],
                        'zaehler'                   => $row['zaehler'],
                        'nenner'                    => $row['nenner'],
                        'gemarkung_name'            => $row['gemarkungsname']
                    ),
                    'x'      => $x,
                    'y'      => $y,
                    'geom'   => @$row['wktgeom'],
                    'gml_id' => $row['gml_id'],
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
