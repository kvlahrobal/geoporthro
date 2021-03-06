<?php

namespace Mapbender\AlkisBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Response;
use ARP\SolrClient2\SolrClient;
use Mapbender\AlkisBundle\Component\ColognePhonetic;

class BaseSearchTwo extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "BasisSucheZwei";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "BasisSucheZwei Description";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
        return array();
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'title' => 'search',
            'tooltip' => 'search',
            'buffer' => 0.5,
            'options' => array(),
//            'dataSrs' => null, set srsData from Solr configuration (parameters.yml)
            'target' => null,
        );
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $configuration = parent::getConfiguration();
        $solr = $this->container->getParameter('solr');
        $configuration['dataSrs'] = $solr['srs'];
        return $configuration;
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbBaseSearchTwo';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\AlkisBundle\Element\Type\BaseSearchTwoAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderAlkisBundle:ElementAdmin:basesearchtwo.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array('mapbender.element.basesearchtwo.js',
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                '@FOMCoreBundle/Resources/public/js/widgets/dropdown.js'),
            'css' => array(
                '@MapbenderAlkisBundle/Resources/public/sass/element/mapbender.element.basesearchtwo.scss',
                '@MapbenderAlkisBundle/Resources/public/sass/element/mapbender.element.basesearchtwo.result.scss')
        );
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
            ->render(
                'MapbenderAlkisBundle:Element:basesearchtwo.html.twig',
                array(
                    'id' => $this->getId(),
                    'title' => $this->getTitle(),
                    'configuration' => $this->getConfiguration()
                )
            );
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
        switch ($action) {
            case 'search':
                return $this->search();
            default:
                throw new NotFoundHttpException('No such action');
        }
    }

    public function tokenize($string)
    {
        return implode(
            " ",
            array_filter(
                explode(" ", preg_replace("/\\W/", " ", $string))
            )
        );
    }

    protected function search()
    {
        // für beide Suchtypen benötigte Parameter einlesen
        $type = $this->container->get('request')->get('type', 'mv_flur');
        $term = $this->container->get('request')->get('term', null);
        $page = $this->container->get('request')->get('page', 1);
        
        // Suchtyp: geocodr-Suche
        if ($type === 'mv_addr' || $type === 'mv_flur') {
            // Konfiguration einlesen
            $conf = $this->container->getParameter('geocodr');
            
            // Suchklasse auswerten
            if ($type === 'mv_flur') {
                $searchclass = 'parcel';
                // Suchwort manipulieren, damit auch Suche nach ALKIS-Flurstückskennzeichen mit schließenden Unterstrichen funktioniert
                if (substr($term, strlen($term) - 2, strlen($term)) === '__')
                    $term = str_replace('_', '', $term);
            } else {
                $searchclass = 'address';
                // Suchwort manipulieren, damit auch Suche nach Apostrophen funktioniert
                $term = strtolower($term);
                if (strpos($term, "′") !== false)
                    $term = str_replace("′", "’", $term);
                elseif (strpos($term, "´") !== false)
                    $term = str_replace("´", "’", $term);
                elseif (strpos($term, "`") !== false)
                    $term = str_replace("`", "’", $term);
                elseif (strpos($term, "‘") !== false)
                    $term = str_replace("‘", "’", $term);
                elseif (strpos($term, "'") !== false)
                    $term = str_replace("'", "’", $term);
                if (strpos($term, "upm ") === 0)
                    $term = str_replace("upm ", "up’m ", $term);
                elseif (strpos($term, "up m ") === 0)
                    $term = str_replace("up m ", "up’m ", $term);
                elseif (strpos($term, "upn ") === 0)
                    $term = str_replace("upn ", "up’n ", $term);
                elseif (strpos($term, "up n ") === 0)
                    $term = str_replace("up n ", "up’n ", $term);
                elseif (strpos($term, "taun k") === 0)
                    $term = str_replace("taun k", "tau’n k", $term);
                elseif (strpos($term, "taun l") === 0)
                    $term = str_replace("taun l", "tau’n l", $term);
                elseif (strpos($term, "tau n ") === 0)
                    $term = str_replace("tau n ", "tau’n ", $term);
                elseif (strpos($term, "nahn ") === 0)
                    $term = str_replace("nahn ", "nah’n ", $term);
                elseif (strpos($term, "nah n ") === 0)
                    $term = str_replace("nah n ", "nah’n ", $term);
                elseif (strpos($term, "inn ") === 0)
                    $term = str_replace("inn ", "in’n ", $term);
                elseif (strpos($term, "in n ") === 0)
                    $term = str_replace("in n ", "in’n ", $term);
                elseif (strpos($term, "hinnru") === 0)
                    $term = str_replace("hinnru", "hin’nru", $term);
                elseif (strpos($term, "hin nru") === 0)
                    $term = str_replace("hin nru", "hin’nru", $term);
                elseif (strpos($term, "eicksch") === 0)
                    $term = str_replace("eicksch", "eick’sch", $term);
                elseif (strpos($term, "eick sch") === 0)
                    $term = str_replace("eick sch", "eick’sch", $term);
                elseif (strpos($term, "ann k") === 0)
                    $term = str_replace("ann k", "an’n k", $term);
                elseif (strpos($term, "ann p") === 0)
                    $term = str_replace("ann p", "an’n p", $term);
                elseif (strpos($term, "ann w") === 0)
                    $term = str_replace("ann w", "an’n w", $term);
                elseif (strpos($term, "an n ") === 0)
                    $term = str_replace("an n ", "an’n ", $term);
                elseif (strpos($term, " runn ") !== false)
                    $term = str_replace(" runn ", " run’n ", $term);
                elseif (strpos($term, " run n ") !== false)
                    $term = str_replace(" run n ", " run’n ", $term);
            }
            
            // Offset ermitteln
            $hits = $conf['hits'];
            $offset = ($page - 1) * $hits;
            
            // Suche durchführen mittels cURL
            $curl = curl_init();
            $term = curl_escape($curl, $term);
            $url = $conf['url'] . 'key=' . $conf['key'] . '&type=' . $conf['type'] . '&class=' . $searchclass . '&offset=' . $offset . '&limit=' . $hits . '&query=' . $term;
            curl_setopt($curl, CURLOPT_URL, $url); 
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            
            // Suchresultat verarbeiten
            $json = json_decode(curl_exec($curl), true); 
            $features = $json['features'];
            $result = $features;
            curl_close($curl);
            
            // für die Pagination benötigte Parameter ermitteln
            $results = $json['properties']['features_total'];
            $currentResults = $json['properties']['features_returned'];
            $pages = ceil($results / $hits);
            
            // Bereinigungsarbeiten
            foreach ($features as $key=>$feature) {
                // x- und y-Wert punkthafter Geometrien abgreifen und separat ablegen
                if ($feature['geometry']['type'] === 'Point') {
                    $result[$key]['x'] = $feature['geometry']['coordinates'][0];
                    $result[$key]['y'] = $feature['geometry']['coordinates'][1];
                // nicht-punkthafte Geometrien in WKT umwandeln
                } else {
                    $result[$key]['wkt'] = strtoupper($feature['geometry']['type']) . '(' . $this->extract($feature['geometry']['coordinates'], $feature['geometry']['type']) . ')';
                }
                // Zusatznamen aus Gemeindenamen entfernen
                if (strpos($feature['properties']['gemeinde_name'], ',') !== false)
                    $result[$key]['properties']['gemeinde_name'] = substr($feature['properties']['gemeinde_name'], 0, strpos($feature['properties']['gemeinde_name'], ','));
                // nur Suchklasse mv_flur: führende 13 bei Gemarkungs- und führende 0 bei Flurnummern sowie Zählern und Nennern entfernen
                if ($type === 'mv_flur') {
                    $result[$key]['properties']['gemarkung_schluessel'] = substr($feature['properties']['gemarkung_schluessel'], 2);
                    if ($feature['properties']['objektgruppe'] === 'Flur') {
                        $result[$key]['properties']['flur'] = ltrim($feature['properties']['flur'], '0');
                    } elseif ($feature['properties']['objektgruppe'] === 'Flurstück') {
                        $result[$key]['properties']['flur'] = ltrim($feature['properties']['flur'], '0');
                        $result[$key]['properties']['zaehler'] = ltrim($feature['properties']['zaehler'], '0');
                        $result[$key]['properties']['nenner'] = ltrim($feature['properties']['nenner'], '0');
                    }
                }
            }
            
            // weitere für die Pagination benötigte Parameter ermitteln
            $currentResults = count($result);
            if ($page > 2)
                $previousPage = $page - 1;
            else
                $previousPage = 1;
            if ($page < $pages)
                $nextPage = $page + 1;
            else
                $nextPage = $pages;
        }
        // Suchtyp: Solr-Suche
        else {
            // Konfiguration einlesen
            $conf = $this->container->getParameter('solr');
            
            // Suchclient initialisieren
            $solr = new SolrClient($conf);
            
            // Suche durchführen
            $solr
                ->limit($conf['hits'])
                ->page($page)
                ->where('type', $type)
                ->orderBy('label', 'asc');
            
            // Suchwort manipulieren
            $term = strtolower($term);
            if (strpos($term, "-") !== false)
                $term = str_replace("-", "", $term);
            if (strpos($term, "/") !== false)
                $term = str_replace("/", "", $term);
            $result = $solr
                ->numericWildcard(true)
                ->wildcardMinStrlen(0)
                // ohne Phonetik
                ->find(null, $this->withoutPhonetic($term));
        }
        
        // Übergabe des Suchresultats sowie weiterer (für die Pagination beim Suchtyp geocodr-Suche benötigter) Parameter an Template
        $html = $this->container->get('templating')->render(
            'MapbenderAlkisBundle:Element:resultstwo.html.twig',
            array(
                'result'         => $result,
                'type'           => $type,
                'results'        => $results,
                'pages'          => $pages,
                'currentPage'    => $page,
                'currentResults' => $currentResults,
                'previousPage'   => $previousPage,
                'nextPage'       => $nextPage
            )
        );

        return new Response($html, 200, array('Content-Type' => 'text/html'));
    }
    
    public function extract($geometry, $type)
    {
        $array = array();
        switch (strtolower($type)) {
            case 'point':
                return $geometry[0] . ' ' . $geometry[1];
            case 'multipoint':
            case 'linestring':
                foreach ($geometry as $geom) {
                    $array[] = $this->extract($geom, 'point');
                }
                return implode(',', $array);
            case 'multilinestring':
            case 'polygon':
                foreach ($geometry as $geom) {
                    $array[] = '(' . $this->extract($geom, 'linestring') . ')';
                }
                return implode(',', $array);
            case 'multipolygon':
                foreach ($geometry as $geom) {
                    $array[] = '(' . $this->extract($geom, 'polygon') . ')';
                }
                return implode(',', $array);
            default:
              return null;
        }
    }
    
    public function addPhonetic($string)
    {
        $result   = "";
        $phonetic = ColognePhonetic::singleton();

        $array = array_filter(
            explode(" ", preg_replace("/[^a-zäöüßÄÖÜ0-9]/i", " ", trim($string)))
        );

        foreach ($array as $val) {
            if (preg_match("/^[a-zäöüßÄÖÜ]+$/i", $val)) {
                $result .= " AND (" . $val. '^20 OR ' . $val . '*^15';
                
                if(!preg_match('/^h+/', $val) && !preg_match('/^i+/', $val)) {
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
