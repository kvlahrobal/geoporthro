<?php
namespace Mapbender\AlkisBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use ARP\SolrClient2\SolrClient;

class IndexAuftragsverwaltungCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setDescription('Reset\'s the solr index.')
        ->setName('hro:index:reset:auftragsverwaltung');
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

        
        $type = 'auftrag';
        
        $solr = new SolrClient(
            $this->getContainer()->getParameter('solr')
        );

        $solr->deleteByQuery('id:' . $type . '_' . '*');
        $solr->commit();

        $conn = $this->getContainer()->get('doctrine.dbal.hro_search_data_connection');

        $limit = 1000;
        $offset = 0;
        $id = 0;

        $output->writeln('Indiziere Auftragsverwaltung fuer HRO-Auftragsverwaltungssuche ... ');


        $stmt = $conn->query("SELECT count(*) AS count FROM regis.auftragsverwaltung WHERE auftrag_art IN ('A', 'E', 'G', 'K', 'M', 'V')");
        $result = $stmt->fetch();

        while ($offset < $result['count']) {
            $stmt = $conn->query("
                SELECT
                 CASE
                  WHEN datum_erledigt IS NULL THEN 0::smallint
                  ELSE 1::smallint
                 END AS erledigt,
                 auftrag_art,
                 substring(auftrag_nummer from 3) AS auftrag_nummer_georg,
                 auftrag_nummer AS auftrag_nummer_hybrid,
                 CASE
                  WHEN auftrag_art = 'K' THEN substring(auftrag_nummer for 4) || lpad(regexp_replace(auftrag_nummer, '^.*[A-Z]+', ''), 5, '0')
                  ELSE NULL::text
                 END AS auftrag_nummer_lah,
                 array_to_string(dokumente, ',') AS dokumente,
                 ST_AsText(ST_Centroid(geometrie)) AS geom,
                 ST_AsText(geometrie) AS wktgeom
                  FROM regis.auftragsverwaltung
                   WHERE auftrag_art IN ('A', 'E', 'G', 'K', 'M', 'V')
                    ORDER BY id
                     LIMIT " . $limit . " OFFSET " . $offset);

            while ($row = $stmt->fetch()) {
                list($x, $y) = $this->prepairPoint($row['geom']);

                $doc = $solr->newDocument();
                $doc->id = $type . '_' . ++$id;

                $doc->text = $this->concat(
                    $row['erledigt'],
                    $row['auftrag_art'],
                    $row['auftrag_nummer_georg'],
                    $row['auftrag_nummer_hybrid'],
                    $row['auftrag_nummer_lah'],
                    $row['dokumente']
                );

                $doc->label = $row['auftrag_nummer_hybrid'];

                $doc->json = json_encode(array(
                    'data'   => array(
                        'erledigt'              => $row['erledigt'],
                        'auftrag_art'           => $row['auftrag_art'],
                        'auftrag_nummer_georg'  => $row['auftrag_nummer_georg'],
                        'auftrag_nummer_hybrid' => $row['auftrag_nummer_hybrid'],
                        'auftrag_nummer_lah'    => $row['auftrag_nummer_lah'],
                        'dokumente'             => $row['dokumente']
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
