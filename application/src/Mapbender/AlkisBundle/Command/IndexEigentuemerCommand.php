<?php
namespace Mapbender\AlkisBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use ARP\SolrClient2\SolrClient;

class IndexEigentuemerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setDescription('Reset\'s the solr index.')
        ->setName('hro:index:reset:eigentuemer');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type     = 'eigen';
        $id       = 0;
        $limit    = 10000;
        $offset   = 0;
        $solr     = new SolrClient(
            $this->getContainer()->getParameter('solr')
        );

        $solr->deleteByQuery('id:' . $type . '_' . '*');
        $solr->commit();

        $conn = $this->getContainer()->get('doctrine.dbal.hro_data_connection');

        $output->writeln('Indiziere Eigentuemer fuer HRO-Eigentuemersuche ... ');

        
        $stmt = $conn->query("SELECT count(*) AS count FROM aaa_ogr.ax_person WHERE endet IS NULL");

        $result = $stmt->fetch();

        while ($offset < $result['count']) {
            $stmt = $conn->query("
                SELECT
                 gml_id,
                 nachnameoderfirma AS nachname,
                 vorname,
                 geburtsdatum,
                 to_char(geburtsdatum::date, 'DD.MM.YYYY') AS geburtsdatum_formatiert
                  FROM aaa_ogr.ax_person
                   WHERE endet IS NULL
                    ORDER BY ogc_fid
                     LIMIT " . $limit . " OFFSET " . $offset);

            while ($row = $stmt->fetch()) {
                $doc = $solr->newDocument();
                $doc->id = $type . '_' . ++$id;
                $doc->text = $this->concat(
                    $row['nachname'],
                    $row['vorname'],
                    $row['gml_id']
                );
                $doc->label = $row['nachname'] . ', ' . $row['vorname'] . ' (' . $row['geburtsdatum_formatiert'] . ')';
                $doc->json = json_encode(array(
                    'label'        => $row['nachname'] . ', ' . $row['vorname'] . ' (' . $row['geburtsdatum_formatiert'] . ')',
                    'nachname'     => $row['nachname'],
                    'vorname'      => $row['vorname'],
                    'geburtsdatum' => $row['geburtsdatum'],
                    'gml_id'       => $row['gml_id']

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

        return array('', '');
    }
}
