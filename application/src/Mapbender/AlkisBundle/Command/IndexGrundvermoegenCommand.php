<?php
namespace Mapbender\AlkisBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Mapbender\AlkisBundle\Component\ColognePhonetic;

use ARP\SolrClient2\SolrClient;

class IndexGrundvermoegenCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setDescription('Reset\'s the solr index.')
        ->setName('hro:index:reset:grundvermoegen');
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

        
        $type = 'grundvermoegen';
        $phonetic = ColognePhonetic::singleton();
        
        $solr = new SolrClient(
            $this->getContainer()->getParameter('solr')
        );

        $solr->deleteByQuery('id:' . $type . '_' . '*');
        $solr->commit();

        $conn = $this->getContainer()->get('doctrine.dbal.hro_search_data_connection');

        $limit = 1000;
        $offset = 0;
        $id = 0;

        $output->writeln('Indiziere Grundvermoegen fuer HRO-Grundvermoegensuche ... ');


        $stmt = $conn->query('SELECT count(*) AS count FROM regis.grundvermoegen');
        $result = $stmt->fetch();

        while ($offset < $result['count']) {
            $stmt = $conn->query("
                SELECT vermoegensbewertung_aktenzeichen,
                regexp_replace(vermoegensbewertung_aktenzeichen, '(\.|-)', '', 'g') AS vermoegensbewertung_aktenzeichen_ohne_sonderzeichen,
                regexp_replace(vermoegensbewertung_aktenzeichen, '(\.|-)', ' ', 'g') AS vermoegensbewertung_aktenzeichen_mit_leerzeichen,
                ST_AsText(ST_Centroid(geometrie)) AS geom,
                ST_AsText(geometrie) AS wktgeom
                FROM regis.grundvermoegen
                LIMIT " . $limit . " OFFSET " . $offset);

            while ($row = $stmt->fetch()) {
                list($x, $y) = $this->prepairPoint($row['geom']);

                $doc = $solr->newDocument();
                $doc->id = $type . '_' . ++$id;
                $doc->text = $this->concat(
                    $row['vermoegensbewertung_aktenzeichen'],
                    $row['vermoegensbewertung_aktenzeichen_ohne_sonderzeichen'],
                    $row['vermoegensbewertung_aktenzeichen_mit_leerzeichen']
                );
                
                $doc->phonetic = $this->addPhonetic($this->concat(
                    $row['vermoegensbewertung_aktenzeichen'],
                    $row['vermoegensbewertung_aktenzeichen_ohne_sonderzeichen'],
                    $row['vermoegensbewertung_aktenzeichen_mit_leerzeichen']
                ));

                $doc->label = "1".$row['vermoegensbewertung_aktenzeichen'].$row['vermoegensbewertung_aktenzeichen_ohne_sonderzeichen'].$row['vermoegensbewertung_aktenzeichen_mit_leerzeichen'];

                $doc->json = json_encode(array(
                    'data'   => array(
                        'type'                              => $type,
                        'vermoegensbewertung_aktenzeichen'  => $row['vermoegensbewertung_aktenzeichen']
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
