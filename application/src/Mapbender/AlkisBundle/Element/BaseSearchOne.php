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
        $conf = $this->container->getParameter('solr');
        $term = $this->container->get('request')->get("term", null);
        $page = $this->container->get('request')->get("page", 1);
        $type = $this->container->get('request')->get("type", 'flur');
        $solr = new SolrClient($conf);

        // Suche
        $solr
            ->limit($conf['hits'])
            ->page($page)
            ->where('type', $type);
        
        // Sortierung
        //if ($type !== 'addr' || ($type === 'addr' && preg_match('/(traße|trasse|tr|tr\.)$/', $term))) {
        if ($type !== 'addr') {
            $solr->orderBy('score desc, label', 'asc');
        } else {
            $solr->orderBy('label', 'asc');
        }
        
        // tatsächliche Suche
        if ($type === 'addr') {
            $result = $solr
                ->numericWildcard(true)
                ->wildcardMinStrlen(0)
                // ohne Phonetik
                ->find(null, $this->withoutPhonetic($term, false, true, false));
        } else if ($type === 'flur') {
            $result = $solr
                ->numericWildcard(true)
                ->wildcardMinStrlen(0)
                // ohne Phonetik
                ->find(null, $this->withoutPhonetic($term, true, false, true));
        } else {
            $result = $solr
                ->numericWildcard(true)
                ->wildcardMinStrlen(0)
                // mit Phonetik
                ->find(null, $this->addPhonetic($term));
        }
            
        // Übergabe an Template
        $html = $this->container->get('templating')->render(
            'MapbenderAlkisBundle:Element:results.html.twig',
            array(
                'result' => $result,
                'type'   => $type
            )
        );

        return new Response($html, 200, array('Content-Type' => 'text/html'));
    }

    public function prepairStreet($string)
    {
        return trim(preg_replace("/(straße|strasse|str|str\.)/i", "", $string));
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

    public function withoutPhonetic($string, $prepairStreet = false, $eszett = false, $prepairFlur = false)
    {
        $result = "";
        if ($prepairStreet === true) {
            $string = $this->prepairStreet($string);
        }
        if ($eszett === true) {
            $string = preg_replace("/traße?$/i", "trasse", $string);
        }
        if ($prepairFlur === true) {
            if ((stripos($string, 'flur') !== false) && (stripos($string, 'flur') > 0)) {
                $string = preg_replace("/flur/i", "", $string);
            }
        }

        $array = array_filter(
            explode(" ", preg_replace("/[^a-zäöüßÄÖÜ0-9]/i", " ", $string))
        );

        foreach ($array as $val) {
            if (preg_match("/^[a-zäöüßÄÖÜ]+$/i", $val)) {
                $result .= " AND (" . $val. "^2" . " OR " . $val . "*^15)";
            } else {
                $result .= " AND (" . $val. "^2" . " OR " . $val . "*^1)";
            }
        }

        return substr(trim($result), 3);
    }
}
