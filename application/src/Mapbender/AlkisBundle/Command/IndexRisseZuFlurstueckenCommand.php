<?php
namespace Mapbender\AlkisBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use ARP\SolrClient2\SolrClient;

class IndexRisseZuFlurstueckenCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setDescription('Reset\'s the solr index.')
        ->setName('hro:index:reset:rissezuflurstuecken');
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

        
        $type = 'risse_fst';
        
        $solr = new SolrClient(
            $this->getContainer()->getParameter('solr')
        );

        $solr->deleteByQuery('id:' . $type . '_' . '*');
        $solr->commit();

        $conn = $this->getContainer()->get('doctrine.dbal.hro_search_data_connection');

        $limit = 1000;
        $offset = 0;
        $id = 0;

        $output->writeln('Indiziere Risse fuer HRO-Suche nach Rissen zu Flurstuecken ... ');


        $stmt = $conn->query("SELECT count(*) AS count FROM alkis.risse_zu_flurstuecken");
        $result = $stmt->fetch();

        while ($offset < $result['count']) {
            $stmt = $conn->query("
                SELECT
                 rpad(regexp_replace(flurstueckskennzeichen, '\D', '', 'g'), 18, '0') AS flurstuecksnummer,
                 flurstueckskennzeichen,
                 array_to_string(risse, ',') AS risse,
                 array_to_string(risse_wkt, 'x') AS risse_wkt
                  FROM alkis.risse_zu_flurstuecken
                   ORDER BY id
                    LIMIT " . $limit . " OFFSET " . $offset);

            while ($row = $stmt->fetch()) {

                $doc = $solr->newDocument();
                $doc->id = $type . '_' . ++$id;

                $doc->text = $this->concat(
                    $row['flurstuecksnummer'],
                    $row['flurstueckskennzeichen']
                );

                $doc->label = $row['flurstueckskennzeichen'];

                $doc->json = json_encode(array(
                    'data'   => array(
                        'flurstuecksnummer'      => $row['flurstuecksnummer'],
                        'flurstueckskennzeichen' => $row['flurstueckskennzeichen'],
                        'risse'                  => $row['risse'],
                        'risse_wkt'              => $row['risse_wkt']
                    )
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
}
