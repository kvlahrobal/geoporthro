<?php

namespace Mapbender\AlkisBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Response;
use ARP\SolrClient2\SolrClient;
use Mapbender\AlkisBundle\Component\ColognePhonetic;

class BaseSearchOne extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "BasisSucheEins";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "BasisSucheEins Description";
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
        return 'mapbender.mbBaseSearchOne';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\AlkisBundle\Element\Type\BaseSearchOneAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderAlkisBundle:ElementAdmin:basesearchone.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array('mapbender.element.basesearchone.js',
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                '@FOMCoreBundle/Resources/public/js/widgets/dropdown.js'),
            'css' => array(
                '@MapbenderAlkisBundle/Resources/public/sass/element/mapbender.element.basesearchone.scss',
                '@MapbenderAlkisBundle/Resources/public/sass/element/mapbender.element.basesearchone.result.scss')
        );
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
            ->render(
                'MapbenderAlkisBundle:Element:basesearchone.html.twig',
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
        $type = $this->container->get('request')->get('type', 'flur');
        $term = $this->container->get('request')->get('term', null);
        $page = $this->container->get('request')->get('page', 1);
        
        // Suchtyp: geocodr-Suche
        if ($type === 'addr' || $type === 'flur') {
            // Konfiguration einlesen
            $conf = $this->container->getParameter('geocodr');
            
            // Suchklasse auswerten
            if ($type === 'flur') {
                $searchclass = 'parcel_hro';
                // Suchwort manipulieren, damit auch Suche nach ALKIS-Flurstückskennzeichen mit schließenden Unterstrichen funktioniert
                if (substr($term, strlen($term) - 2, strlen($term)) === '__')
                    $term = str_replace('_', '', $term);
            } else {
                $searchclass = 'address_hro';
                // Suchwort manipulieren, damit auch Suche nach Apostrophen funktioniert
                if (strpos($term, "′") !== false)
                    $term = str_replace("′", "’", $term);
                elseif (strpos($term, "´") !== false)
                    $term = str_replace("´", "’", $term);
                elseif (strpos($term, "`") !== false)
                    $term = str_replace("`", "’", $term);
                elseif (strpos($term, "’") !== false)
                    $term = str_replace("’", "’", $term);
                elseif (strpos($term, "‘") !== false)
                    $term = str_replace("‘", "’", $term);
                elseif (strpos($term, "'") !== false)
                    $term = str_replace("'", "’", $term);
            }
            
            // Suche durchführen mittels cURL
            $curl = curl_init();
            $term = curl_escape($curl, $term);
            $url = $conf['url'] . 'key=' . $conf['key'] . '&type=' . $conf['type'] . '&limit=' . $conf['limit'] . '&class=' . $searchclass . '&query=' . $term;
            curl_setopt($curl, CURLOPT_URL, $url); 
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            
            // Suchresultat verarbeiten
            $json = json_decode(curl_exec($curl), true); 
            $features = $json['features'];
            $result = $features;
            curl_close($curl);
            
            // für die Pagination und die Ermittlung des aktuellen Teils des Suchresultats benötigte Parameter ermitteln
            $results = count($result);
            $hits = $conf['hits'];
            $pages = ceil($results / $hits);
            
            // aktuellen Teil des Suchresultats ermitteln
            $result = array_slice($result, ($page - 1) * $hits, $hits);
            $features = $result;
            
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
                // nur Suchklasse flur: führende 13 bei Gemarkungs- und führende 0 bei Flurnummern sowie Zählern und Nennern entfernen
                if ($type === 'flur') {
                    $result[$key]['properties']['gemarkung_schluessel'] = substr($feature['properties']['gemarkung_schluessel'], 2);
                    if ($feature['properties']['objektgruppe'] === 'Flur HRO') {
                        $result[$key]['properties']['flur'] = ltrim($feature['properties']['flur'], '0');
                    } elseif ($feature['properties']['objektgruppe'] === 'Flurstück HRO') {
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
            
            // Suchresultat verarbeiten
            $result = $solr
                ->numericWildcard(true)
                ->wildcardMinStrlen(0)
                ->find(null, $this->addPhonetic($term));
        }
        
        // Übergabe des Suchresultats sowie weiterer (für die Pagination beim Suchtyp geocodr-Suche benötigter) Parameter an Template
        $html = $this->container->get('templating')->render(
            'MapbenderAlkisBundle:Element:results.html.twig',
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
                
                if(!preg_match('/^h+$/', $val) && !preg_match('/^i+$/', $val)) {
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
