<?php
namespace Mapbender\AlkisBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
//use Mapbender\AlkisBundle\Component\ColognePhonetic;

use ARP\SolrClient2\SolrClient;

class IndexLeuchtenCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setDescription('Reset\'s the solr index.')
        ->setName('hro:index:reset:leuchten');
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

        
        $type = 'leuchten';
        
        $solr = new SolrClient(
            $this->getContainer()->getParameter('solr')
        );

        $solr->deleteByQuery('id:' . $type . '_' . '*');
        $solr->commit();

        $conn = $this->getContainer()->get('doctrine.dbal.hro_search_data_connection');

        $limit = 500;
        $offset = 0;
        $id = 0;

        $output->writeln('Indiziere Leuchten fuer HRO-Leuchtensuche ... ');


        $stmt = $conn->query('SELECT count(*) AS count FROM fachdaten.swrag_leuchten_leuchtentragsysteme_regis');
        $result = $stmt->fetch();

        while ($offset < $result['count']) {
            $stmt = $conn->query("
                SELECT
                 tragsystem_nummer,
                 regexp_replace(tragsystem_nummer, '\D','','g') AS tragsystem_nummer_nur_zahlen,
                 tragsystem_mslink,
                 nummer,
                 regexp_replace(nummer, '\D','','g') AS nummer_nur_zahlen,
                 nummer_zusatz,
                 mslink,
                 ST_AsText(ST_Centroid(geometrie)) AS geom,
                 ST_AsText(geometrie) AS wktgeom
                  FROM fachdaten.swrag_leuchten_leuchtentragsysteme_regis
                   ORDER BY id
                    LIMIT " . $limit . " OFFSET " . $offset);

            while ($row = $stmt->fetch()) {
                list($x, $y) = $this->prepairPoint($row['geom']);

                $doc = $solr->newDocument();
                $doc->id = $type . '_' . ++$id;
                $doc->text = $this->concat(
                    $row['tragsystem_nummer_nur_zahlen'],
                    $row['tragsystem_nummer'],
                    $row['tragsystem_mslink'],
                    $row['nummer_nur_zahlen'],
                    $row['nummer'],
                    $row['nummer_zusatz'],
                    $row['mslink']
                );

                $doc->label = "1".$row['tragsystem_nummer'].$row['tragsystem_mslink'].$row['nummer'].$row['nummer_zusatz'].$row['mslink'];

                $doc->json = json_encode(array(
                    'data'   => array(
                        'type'              => $type,
                        'tragsystem_nummer' => $row['tragsystem_nummer'],
                        'tragsystem_mslink' => $row['tragsystem_mslink'],
                        'nummer'            => $row['nummer'],
                        'nummer_zusatz'     => $row['nummer_zusatz'],
                        'mslink'            => $row['mslink']
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
