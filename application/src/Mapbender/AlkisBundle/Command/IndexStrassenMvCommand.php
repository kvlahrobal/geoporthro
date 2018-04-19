<?php
namespace Mapbender\AlkisBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
//use Mapbender\AlkisBundle\Component\ColognePhonetic;

use ARP\SolrClient2\SolrClient;

class IndexStrassenMvCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setDescription('Reset\'s the solr index.')
            ->setName('hro:index:reset:mv_strassen');
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


        $type = 'mv_strasse';

        $solr = new SolrClient(
            $this->getContainer()->getParameter('solr')
        );

        $solr->deleteByQuery('id:' . $type . '_' . '*');
        $solr->commit();

        $conn = $this->getContainer()->get('doctrine.dbal.hro_search_data_connection');

        $limit = 500;
        $offset = 0;
        $id = 0;

        $output->writeln('Indiziere Strassen fuer MV-Adressensuche ... ');

        $stmt = $conn->query('SELECT count(*) AS count FROM regis.strassen_alle_ohne_mehrfachnamen');
        $result = $stmt->fetch();
        $count = intval($result['count']);
        $stmt = $conn->query('SELECT count(*) AS count FROM regis.strassen_alle_mehrfachnamen');
        $result = $stmt->fetch();
        $count = $count + intval($result['count']);

        while ($offset < $count) {
            $stmt = $conn->query("
                SELECT uuid,
                CASE
                    WHEN (gemeinde_name % gemeindeteil_name OR strasse_name % gemeindeteil_name) THEN 'einmalig'
                    ELSE NULL
                END AS einmalig,
                regexp_replace(gemeinde_name, '\, .*$', '') AS gemeinde_name,
                gemeindeteil_name,
                CASE
                    WHEN NOT (gemeinde_name % gemeindeteil_name OR strasse_name % gemeindeteil_name) THEN gemeindeteil_name || ', ' || strasse_name
                    ELSE strasse_name
                END AS strasse_name,
                ST_AsText(ST_Centroid(geometrie)) AS geom,
                ST_AsText(geometrie) AS wktgeom
                FROM regis.strassen_alle_ohne_mehrfachnamen
                UNION SELECT uuid,
                NULL AS einmalig,
                regexp_replace(gemeinde_name, '\, .*$', '') AS gemeinde_name,
                gemeindeteil_name,
                gemeindeteil_name || ', ' || strasse_name AS strasse_name,
                ST_AsText(ST_Centroid(geometrie)) AS geom,
                ST_AsText(geometrie) AS wktgeom
                FROM regis.strassen_alle_mehrfachnamen
                ORDER BY uuid
                LIMIT " . $limit . " OFFSET " . $offset);

            while ($row = $stmt->fetch()) {
                list($x, $y) = $this->prepairPoint($row['geom']);

                $doc = $solr->newDocument();
                $doc->id = $type . '_' . ++$id;
                $doc->text = $this->concat(
                    $row['gemeinde_name'],
                    $this->prepairStreet($row['strasse_name'])/*,
                    $row['gemeindeteil_name']*/
                );
                /*$doc->phonetic = $this->addPhonetic($this->concat(
                    $row['gemeinde_name'],
                    $row['strasse_name']
                ));*/

                $doc->label = "3".$row['gemeinde_name'].$row['strasse_name'];

                $doc->json = json_encode(array(
                    'data' => array(
                        'type'                  => $type,
                        'einmalig'              => $row['einmalig'],
                        'gemeinde_name'         => $row['gemeinde_name'],
                        'gemeindeteil_name'     => $row['gemeindeteil_name'],
                        'strasse_name'          => $row['strasse_name']
                    ),
                    'x' => $x,
                    'y' => $y,
                    'geom' => $row['wktgeom']
                ));
                $doc->type = 'mv_addr';
                $solr->appendDocument($doc);
            }

            $solr->commit();
            $offset += $limit;

            $output->writeln("\t" . (
                $offset > $count ? $count : $offset
            ) . " von " . $count . " indiziert.");
        }

        $solr->commit();
        $solr->optimize();

        $output->writeln('fertig');
    }

    public function concat()
    {
        return implode(" ", array_filter(func_get_args()));
    }

    public function prepairStreet($string)
    {
        return trim(preg_replace("/tr\.$/i", "trasse", $string));
    }

    public function prepairPoint($p)
    {
        if (substr($p, 0, 5) === 'POINT') {
            return explode(' ', substr($p, 6, -1));
        }

        return array('','');
    }

    /*public function addPhonetic($string)
    {
        $phonetic = ColognePhonetic::singleton();
        $phoneticArray = array();

        $words = array_filter(
            explode(" ", preg_replace("/[^a-zäöüßÄÖÜ0-9]/i", " ", $string))
        );

        foreach ($words as $word) {
            if (preg_match("/^[a-zäöüßÄÖÜ]+$/i", $word)) {
                $phoneticArray[] = $phonetic->encode($word);
            }
        }

        return $phoneticArray;
    }*/
}
