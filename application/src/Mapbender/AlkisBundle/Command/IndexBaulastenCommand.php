<?php
namespace Mapbender\AlkisBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use ARP\SolrClient2\SolrClient;

class IndexBaulastenCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setDescription('Reset\'s the solr index.')
        ->setName('hro:index:reset:baulasten');
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

        
        $type = 'baulasten';
        
        $solr = new SolrClient(
            $this->getContainer()->getParameter('solr')
        );

        $solr->deleteByQuery('id:' . $type . '_' . '*');
        $solr->commit();

        $conn = $this->getContainer()->get('doctrine.dbal.hro_data_connection');

        $limit = 1000;
        $offset = 0;
        $id = 0;

        $output->writeln('Indiziere Baulasten fuer HRO-Baulastensuche ... ');


        $stmt = $conn->query("SELECT count(*) FROM aaa_ogr.ax_bauraumoderbodenordnungsrecht WHERE endet IS NULL AND artderfestlegung = 2610");
        $result = $stmt->fetch();

        while ($offset < $result['count']) {
            $stmt = $conn->query("
                SELECT
                 bezeichnung,
                 ST_AsText(ST_Centroid(wkb_geometry)) AS geom,
                 ST_AsText(wkb_geometry) AS wktgeom
                  FROM aaa_ogr.ax_bauraumoderbodenordnungsrecht
                   WHERE endet IS NULL
                   AND artderfestlegung = 2610
                    ORDER BY gml_id
                     LIMIT " . $limit . " OFFSET " . $offset);

            while ($row = $stmt->fetch()) {
                list($x, $y) = $this->prepairPoint($row['geom']);

                $doc = $solr->newDocument();
                $doc->id = $type . '_' . ++$id;

                $doc->text = $row['bezeichnung'];

                $doc->label = $row['bezeichnung'];

                $doc->json = json_encode(array(
                    'data'   => array(
                        'bezeichnung' => $row['bezeichnung']
                    ),
                    'x'      => $x,
                    'y'      => $y,
                    'geom'   => $row['wktgeom'],
                ));

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

    public function prepairPoint($p)
    {
        if (substr($p, 0, 5) === 'POINT') {
            return explode(' ', substr($p, 6, -1));
        }

        return array('','');
    }
}
