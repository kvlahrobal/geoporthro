<?php
namespace Mapbender\AlkisBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use ARP\SolrClient2\SolrClient;

class IndexGrundbuchCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setDescription('Reset\'s the solr index.')
        ->setName('hro:index:reset:grundbuchblaetter');
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

        
        $type = 'grund';
        
        $solr = new SolrClient(
            $this->getContainer()->getParameter('solr')
        );

        $solr->deleteByQuery('id:' . $type . '_' . '*');
        $solr->commit();

        $conn = $this->getContainer()->get('doctrine.dbal.hro_data_connection');

        $limit = 10000;
        $offset = 0;
        $id = 0;

        $output->writeln('Indiziere Grundbuchblaetter fuer HRO-Grundbuchblaettersuche ... ');


        $stmt = $conn->query("SELECT count(*) AS count FROM aaa_ogr.ax_buchungsblatt WHERE endet IS NULL");
        $result = $stmt->fetch();

        while ($offset < $result['count']) {
            $stmt = $conn->query("
                SELECT
                 bl.gml_id,
                 bl.land || bl.bezirk AS schluesselgesamt,
                 bl.bezirk,
                 bz.bezeichnung AS bezirkname,
                 ltrim(buchungsblattnummermitbuchstabenerweiterung, '0') AS gb_blatt,
                 bl.buchungsblattkennzeichen,
                 bl.bezirk || '-' || buchungsblattnummermitbuchstabenerweiterung AS buchungsblattkennzeichen_alb_alk
                  FROM aaa_ogr.ax_buchungsblatt bl, aaa_ogr.ax_buchungsblattbezirk bz
                   WHERE bl.bezirk = bz.bezirk
                   AND bl.endet IS NULL
                   AND bz.endet IS NULL
                    ORDER BY bl.ogc_fid
                     LIMIT " . $limit . " OFFSET " . $offset);

            while ($row = $stmt->fetch()) {
                $doc = $solr->newDocument();
                $doc->id = $type . '_' . ++$id;

                $doc->text = $this->concat(
                    $row['schluesselgesamt'],
                    $row['bezirkname'],
                    $row['bezirkname'],
                    $row['bezirk'],
                    $row['flurstueckskennzeichen'],
                    $row['gb_blatt'],
                    $row['buchungsblattkennzeichen'],
                    $row['buchungsblattkennzeichen_alb_alk'],
                    $row['gml_id']
                );

                $doc->label = $row['bezirkname'].' '.$row['bezirk'].' '.$row['gb_blatt'];

                $doc->json = json_encode(array(
                    'bezirkname' => $row['bezirkname'],
                    'bezirk' => $row['bezirk'],
                    'grundbuchblatt' => $row['gb_blatt'],
                    'gml_id' =>$row['gml_id']
                ));

                $doc->gmlid = $row['gml_id'];
                $doc->type = $type;

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
}
