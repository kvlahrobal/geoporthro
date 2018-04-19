<?php
namespace Mapbender\AlkisBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Mapbender\AlkisBundle\Component\ColognePhonetic;

use ARP\SolrClient2\SolrClient;

class IndexBetriebeGewerblicherArtCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setDescription('Reset\'s the solr index.')
        ->setName('hro:index:reset:betriebegewerblicherart');
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

        
        $type = 'betriebegewerblicherart';
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

        $output->writeln('Indiziere Betriebe gewerblicher Art fuer HRO-Suche nach Betrieben gewerblicher Art ... ');


        $stmt = $conn->query("SELECT count(*) AS count FROM regis.realnutzungsarten WHERE realnutzungsarten LIKE '%BgA%'");
        $result = $stmt->fetch();

        while ($offset < $result['count']) {
            $stmt = $conn->query("
                SELECT gemarkung_name,
                gemarkung_schluessel,
                substring(gemarkung_schluessel from 1 for 2) AS land_schluessel,
                substring(gemarkung_schluessel from 3) AS gemarkung_schluessel_kurz,
                flur::int AS flur_kurz,
                zaehler::int AS zaehler_kurz,
                nenner::int AS nenner_kurz,
                flurstueckskennzeichen,
                vermoegensbewertung_aktenzeichen,
                regexp_replace(vermoegensbewertung_aktenzeichen, '(\.|-)', '', 'g') AS vermoegensbewertung_aktenzeichen_ohne_sonderzeichen,
                regexp_replace(vermoegensbewertung_aktenzeichen, '(\.|-)', ' ', 'g') AS vermoegensbewertung_aktenzeichen_mit_leerzeichen,
                realnutzungsarten,
                bga_bemerkung,
                ST_AsText(ST_Centroid(geometrie)) AS geom,
                ST_AsText(geometrie) AS wktgeom
                FROM regis.realnutzungsarten
                WHERE realnutzungsarten LIKE '%BgA%'
                LIMIT " . $limit . " OFFSET " . $offset);

            while ($row = $stmt->fetch()) {
                list($x, $y) = $this->prepairPoint($row['geom']);

                $doc = $solr->newDocument();
                $doc->id = $type . '_' . ++$id;
                $doc->text = $this->concat(
                    $row['nenner_kurz'] . ' ' . $row['zaehler_kurz'] . ' ' . $row['flur_kurz'] . ' ' . $row['gemarkung_schluessel_kurz'] . ' ' . $row['gemarkung_schluessel'] . ' ' . $row['gemarkung_name'],
                    $row['vermoegensbewertung_aktenzeichen'],
                    $row['vermoegensbewertung_aktenzeichen_ohne_sonderzeichen'],
                    $row['vermoegensbewertung_aktenzeichen_mit_leerzeichen'],
                    $row['realnutzungsarten'],
                    $row['bga_bemerkung']
                );
                
                $doc->phonetic = $this->addPhonetic($this->concat(
                    $row['nenner_kurz'] . ' ' . $row['zaehler_kurz'] . ' ' . $row['flur_kurz'] . ' ' . $row['gemarkung_schluessel_kurz'] . ' ' . $row['gemarkung_schluessel'] . ' ' . $row['gemarkung_name'],
                    $row['vermoegensbewertung_aktenzeichen'],
                    $row['vermoegensbewertung_aktenzeichen_ohne_sonderzeichen'],
                    $row['vermoegensbewertung_aktenzeichen_mit_leerzeichen'],
                    $row['realnutzungsarten'],
                    $row['bga_bemerkung']
                ));

                $doc->label = "1".$row['flurstueckskennzeichen'].$row['vermoegensbewertung_aktenzeichen'].$row['vermoegensbewertung_aktenzeichen_ohne_sonderzeichen'].$row['vermoegensbewertung_aktenzeichen_mit_leerzeichen'].$row['realnutzungsarten'].$row['bga_bemerkung'];

                $doc->json = json_encode(array(
                    'data'   => array(
                        'type'                              => $type,
                        'flurstueckskennzeichen'            => $row['flurstueckskennzeichen'],
                        'vermoegensbewertung_aktenzeichen'  => $row['vermoegensbewertung_aktenzeichen'],
                        'realnutzungsarten'                 => $row['realnutzungsarten'],
                        'bga_bemerkung'                     => $row['bga_bemerkung']
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
