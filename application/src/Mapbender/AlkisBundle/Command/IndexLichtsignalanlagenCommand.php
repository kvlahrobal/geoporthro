<?php
namespace Mapbender\AlkisBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
//use Mapbender\AlkisBundle\Component\ColognePhonetic;

use ARP\SolrClient2\SolrClient;

class IndexLichtsignalanlagenCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setDescription('Reset\'s the solr index.')
        ->setName('hro:index:reset:lichtsignalanlagen');
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

        
        $type = 'lichtsignalanlagen';
        
        $solr = new SolrClient(
            $this->getContainer()->getParameter('solr')
        );

        $solr->deleteByQuery('id:' . $type . '_' . '*');
        $solr->commit();

        $conn = $this->getContainer()->get('doctrine.dbal.hro_search_data_connection');

        $limit = 500;
        $offset = 0;
        $id = 0;

        $output->writeln('Indiziere Lichtsignalanlagen fuer HRO-Lichtsignalanlagensuche ... ');


        $stmt = $conn->query('SELECT count(*) AS count FROM fachdaten_strassenbezug.swrag_lichtsignalanlagensteuergeraete');
        $result = $stmt->fetch();

        while ($offset < $result['count']) {
            $stmt = $conn->query("
                SELECT
                 id_fachsystem AS nummer,
                 regexp_replace(id_fachsystem, '\D','','g') AS nummer_nur_zahlen,
                 bezeichnung,
                 knoten_nummer,
                 regexp_replace(knoten_nummer, '\D','','g') AS knoten_nummer_nur_zahlen,
                 ST_AsText(ST_Centroid(geometrie)) AS geom,
                 ST_AsText(geometrie) AS wktgeom
                  FROM fachdaten_strassenbezug.swrag_lichtsignalanlagensteuergeraete
                   ORDER BY uuid
                    LIMIT " . $limit . " OFFSET " . $offset);

            while ($row = $stmt->fetch()) {
                list($x, $y) = $this->prepairPoint($row['geom']);

                $doc = $solr->newDocument();
                $doc->id = $type . '_' . ++$id;
                $doc->text = $this->concat(
                    $row['nummer_nur_zahlen'],
                    $row['nummer'],
                    $row['bezeichnung'],
                    $row['knoten_nummer_nur_zahlen'],
                    $row['knoten_nummer']
                );

                $doc->label = "1".$row['nummer'].$row['bezeichnung'].$row['knoten_nummer'];

                $doc->json = json_encode(array(
                    'data'   => array(
                        'type'              => $type,
                        'nummer'            => $row['nummer'],
                        'bezeichnung'       => $row['bezeichnung'],
                        'knoten_nummer'     => $row['knoten_nummer']
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
