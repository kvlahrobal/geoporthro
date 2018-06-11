<?php

namespace Mapbender\AlkisBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Response;
use ARP\SolrClient2\SolrClient;
use Mapbender\AlkisBundle\Component\ColognePhonetic;

class BaseSearchTwo extends Element
{
  
    private $globalResult;
    private $globalResults;
    private $globalPages;

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
        $type = $this->container->get('request')->get("type", 'mv_flur');
        $term = $this->container->get('request')->get("term", null);
        
        // geocodr-Suche
        if ($type === 'mv_addr' || $type === 'mv_flur') {
          
            $conf = $this->container->getParameter('geocodr');
            
            $curl = curl_init();
            $term = curl_escape($curl, $term);
            $url = $conf['url'] . 'key=' . $conf['key'] . '&type=' . $conf['type'] . '&class=address&query='. $term;
            curl_setopt($curl, CURLOPT_URL, $url); 
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $json = json_decode(curl_exec($curl), true); 
            $features = $json['features'];
            $result = $features;
            curl_close($curl);
            foreach ($features as $key=>$feature) {
                if (strpos($feature['properties']['gemeinde_name'], ',') !== false)
                    $result[$key]['properties']['gemeinde_name'] = substr($feature['properties']['gemeinde_name'], 0, strpos($feature['properties']['gemeinde_name'], ','));
            }
            $results = count($result);
            $hits = $conf['hits'];
            $pages = ceil($results / $hits);
            $this->globalResult = $result;
            $result = array_slice($result, 0, $hits);
            $this->globalResults = $results;
            $this->globalPages = $pages;
            $currentPage = 1;
            $currentResults = $hits;
            if ($pages > 1)
                $nextPage = 2;

        }
        // Solr-Suche
        else {
          
            $conf = $this->container->getParameter('solr');
            $page = $this->container->get('request')->get("page", 1);
            
            $solr = new SolrClient($conf);
            $solr
                ->limit($conf['hits'])
                ->page($page)
                ->where('type', $type)
                ->orderBy('label', 'asc');
            $result = $solr
                ->numericWildcard(true)
                ->wildcardMinStrlen(0)
                ->find(null, $this->addPhonetic($term));

        }
        
        // Übergabe an Template
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
        $result = $this->globalResult;
        
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
