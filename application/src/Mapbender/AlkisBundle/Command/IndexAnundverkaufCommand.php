<?php
namespace Mapbender\AlkisBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Mapbender\AlkisBundle\Component\ColognePhonetic;

use ARP\SolrClient2\SolrClient;

class IndexAnundverkaufCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setDescription('Reset\'s the solr index.')
        ->setName('hro:index:reset:anundverkauf');
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

        
        $type = 'anundverkauf';
        $phonetic = ColognePhonetic::singleton();
        
        $solr = new SolrClient(
            $this->getContainer()->getParameter('solr')
        );

        $solr->deleteByQuery('id:' . $type . '_' . '*');
        $solr->commit();

        $conn = $this->getContainer()->get('doctrine.dbal.hro_search_data_connection');

        $limit = 10;
        $offset = 0;
        $id = 0;

        $output->writeln('Indiziere An- und Verkauf fuer HRO-Suche nach An- und Verkauf ... ');


        $stmt = $conn->query("SELECT count(*) FROM (SELECT uuid FROM fachdaten_flurstuecksbezug.realnutzungsarten_regis_hro WHERE realnutzungsarten ~ 'Ankauf' OR realnutzungsarten ~ 'ED' OR realnutzungsarten = 'KG' OR regexp_replace(realnutzungsarten, ' ', '', 'g') ~ '\,KG$' OR regexp_replace(realnutzungsarten, ' ', '', 'g') ~ 'KG\,' OR realnutzungsarten ~ 'UV' UNION SELECT uuid FROM fachdaten_flurstuecksbezug.an_und_verkauf_regis_hro WHERE realnutzungsarten ~ 'Ankauf' OR realnutzungsarten = 'KG' OR regexp_replace(realnutzungsarten, ' ', '', 'g') ~ '\,KG$' OR regexp_replace(realnutzungsarten, ' ', '', 'g') ~ 'KG\,' OR realnutzungsarten = 'SO' OR regexp_replace(realnutzungsarten, ' ', '', 'g') ~ '\,SO$' OR regexp_replace(realnutzungsarten, ' ', '', 'g') ~ 'SO\,') AS tabelle");
        $result = $stmt->fetch();

        while ($offset < $result['count']) {
            $stmt = $conn->query("
                SELECT
                 uuid,
                 gemarkung_name,
                 gemarkung_schluessel,
                 substring(gemarkung_schluessel from 1 for 2) AS land_schluessel,
                 substring(gemarkung_schluessel from 3) AS gemarkung_schluessel_kurz,
                 flur::int AS flur_kurz,
                 zaehler::int AS zaehler_kurz,
                 nenner::int AS nenner_kurz,
                 flurstueckskennzeichen,
                 aktenzeichen_anundverkauf AS aktenzeichen,
                 bemerkungen_anundverkauf AS bemerkungen,
                 realnutzungsarten,
                 ST_AsText(ST_Centroid(geometrie)) AS geom,
                 ST_AsText(geometrie) AS wktgeom
                  FROM fachdaten_flurstuecksbezug.realnutzungsarten_regis_hro
                   WHERE realnutzungsarten ~ 'Ankauf'
                   OR realnutzungsarten ~ 'ED'
                   OR realnutzungsarten = 'KG'
                   OR regexp_replace(realnutzungsarten, ' ', '', 'g') ~ '\,KG$'
                   OR regexp_replace(realnutzungsarten, ' ', '', 'g') ~ 'KG\,'
                   OR realnutzungsarten ~ 'UV'
                UNION SELECT
                 uuid,
                 gemarkung_name,
                 gemarkung_schluessel,
                 substring(gemarkung_schluessel from 1 for 2) AS land_schluessel,
                 substring(gemarkung_schluessel from 3) AS gemarkung_schluessel_kurz,
                 flur::int AS flur_kurz,
                 zaehler::int AS zaehler_kurz,
                 nenner::int AS nenner_kurz,
                 flurstueckskennzeichen,
                 aktenzeichen,
                 bemerkungen,
                 realnutzungsarten,
                 ST_AsText(ST_Centroid(geometrie)) AS geom,
                 ST_AsText(geometrie) AS wktgeom
                  FROM fachdaten_flurstuecksbezug.an_und_verkauf_regis_hro
                   WHERE realnutzungsarten ~ 'Ankauf'
                   OR realnutzungsarten = 'KG'
                   OR regexp_replace(realnutzungsarten, ' ', '', 'g') ~ '\,KG$'
                   OR regexp_replace(realnutzungsarten, ' ', '', 'g') ~ 'KG\,'
                   OR realnutzungsarten = 'SO'
                   OR regexp_replace(realnutzungsarten, ' ', '', 'g') ~ '\,SO$' OR regexp_replace(realnutzungsarten, ' ', '', 'g') ~ 'SO\,'
                    ORDER BY uuid
                     LIMIT " . $limit . " OFFSET " . $offset);

            while ($row = $stmt->fetch()) {
                list($x, $y) = $this->prepairPoint($row['geom']);

                $doc = $solr->newDocument();
                $doc->id = $type . '_' . ++$id;
                $doc->text = $this->concat(
                    $row['nenner_kurz'] . ' ' . $row['zaehler_kurz'] . ' ' . $row['flur_kurz'] . ' ' . $row['gemarkung_schluessel_kurz'] . ' ' . $row['gemarkung_schluessel'] . ' ' . $row['gemarkung_name'],
                    $row['aktenzeichen'],
                    $row['bemerkungen'],
                    $row['realnutzungsarten']
                );
                
                $doc->phonetic = $this->addPhonetic($this->concat(
                    $row['nenner_kurz'] . ' ' . $row['zaehler_kurz'] . ' ' . $row['flur_kurz'] . ' ' . $row['gemarkung_schluessel_kurz'] . ' ' . $row['gemarkung_schluessel'] . ' ' . $row['gemarkung_name'],
                    $row['aktenzeichen'],
                    $row['bemerkungen'],
                    $row['realnutzungsarten']
                ));

                $doc->label = "1".$row['flurstueckskennzeichen'].$row['aktenzeichen'].$row['bemerkungen'].$row['realnutzungsarten'];

                $doc->json = json_encode(array(
                    'data'   => array(
                        'type'                   => $type,
                        'flurstueckskennzeichen' => $row['flurstueckskennzeichen'],
                        'aktenzeichen'           => $row['aktenzeichen'],
                        'bemerkungen'            => $row['bemerkungen'],
                        'realnutzungsarten'      => $row['realnutzungsarten']
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

    public function addPhonetic($string)
    {
        $result   = "";
        $phonetic = ColognePhonetic::singleton();

        $array = array_filter(
            explode(" ", preg_replace("/[^a-zäöüßÄÖÜ0-9]/i", " ", $string))
        );

        foreach ($array as $val) {
            if (preg_match("/^[a-zäöüßÄÖÜ]+$/i", $val)) {
                $result .= " AND (" . $val. '^20 OR ' . $val . '*^15';
                
                if($val !== 'h') {
                    $result .= ' OR phonetic:' . $phonetic->encode($val) . '^1'
                    . ' OR phonetic:' . $phonetic->encode($val) . '*^0.5';
                }

                $result .= ")";
            } else {
                $result .= " AND (" . $val. '^2' . " OR " . $val . "*^1)";
            }
        }

        return substr(trim($result), 3);
    }
}
