<?php

namespace Mapbender\AlkisBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Response;
use ARP\SolrClient2\SolrClient;
use Mapbender\AlkisBundle\Component\ColognePhonetic;

class BaseSearchTwo extends Element
{
    // globale Variablen für Pagination
    private $globalResult = array();
    private $globalResults = 0;
    private $globalPages = 0;

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
            case 'pagination':
                return $this->pagination();
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
        
        // Suchtyp: geocodr-Suche
        if ($type === 'mv_addr' || $type === 'mv_flur') {

            // Konfiguration einlesen
            $conf = $this->container->getParameter('geocodr');
            
            // Suche durchführen mittels cURL
            $curl = curl_init();
            $term = curl_escape($curl, $term);
            $url = $conf['url'] . 'key=' . $conf['key'] . '&type=' . $conf['type'] . '&class=address&query='. $term;
            curl_setopt($curl, CURLOPT_URL, $url); 
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            
            // Suchresultat verarbeiten
            $json = json_decode(curl_exec($curl), true); 
            $features = $json['features'];
            $result = $features;
            curl_close($curl);
            
            // Zusatznamen aus Gemeindenamen entfernen
            foreach ($features as $key=>$feature) {
                if (strpos($feature['properties']['gemeinde_name'], ',') !== false)
                    $result[$key]['properties']['gemeinde_name'] = substr($feature['properties']['gemeinde_name'], 0, strpos($feature['properties']['gemeinde_name'], ','));
            }
            
            // für die Pagination benötigte Parameter ermitteln
            $results = count($result);
            $hits = $conf['hits'];
            $pages = ceil($results / $hits);
            $currentPage = 1;
            if ($results < $hits)
              $currentResults = $results;
            else
              $currentResults = $hits;
            if ($pages > 1)
                $nextPage = 2;
            
            // für die Pagination benötigte globale auf entsprechende lokale Variablen setzen
            global $globalResult;
            global $globalResults;
            global $globalPages;
            $globalResult = $result;
            $globalResults = $results;
            $globalPages = $pages;
        
            // ersten Teil des Suchresultats ermitteln
            $result = array_slice($result, 0, $hits);

        }
        // Suchtyp: Solr-Suche
        else {

            // Konfiguration einlesen
            $conf = $this->container->getParameter('solr');
            
            // weiteren Parameter einlesen
            $page = $this->container->get('request')->get('page', 1);
            
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
            'MapbenderAlkisBundle:Element:resultstwo.html.twig',
            array(
                'result'         => $result,
                'type'           => $type,
                'results'        => $results,
                'pages'          => $pages,
                'currentPage'    => $currentPage,
                'currentResults' => $currentResults,
                'previousPage'   => $previousPage,
                'nextPage'       => $nextPage
            )
        );

        return new Response($html, 200, array('Content-Type' => 'text/html'));
    }
    
    public function pagination()
    {
        // Konfiguration einlesen
        $conf = $this->container->getParameter('geocodr');

        // lokale auf entsprechende globale Variablen setzen
        global $globalResult;
        global $globalResults;
        global $globalPages;
        $result = $globalResult;
        $results = $globalResults;
        $pages = $globalPages;
        
        // benötigte Parameter einlesen
        $type = $this->container->get('request')->get('type', 'mv_flur');
        $page = $this->container->get('request')->get('page', 1);
        
        // aktuellen Teil des Suchresultats ermitteln
        $hits = $conf['hits'];
        $result = array_slice($result, ($page - 1) * $hits, $hits);
        
        // benötigte Parameter ermitteln
        $currentResults = count($result);
        if ($page > 2)
            $previousPage = $page - 1;
        else
            $previousPage = 1;
        if ($page < $pages)
            $nextPage = $page + 1;
        else
            $nextPage = $pages;
        
        // Übergabe des aktuellen Teils des Suchresultats sowie weiterer benötigter Parameter an Template
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
