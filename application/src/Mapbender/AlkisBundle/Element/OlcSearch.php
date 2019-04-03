<?php

namespace Mapbender\AlkisBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Response;

class OlcSearch extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "Open-Location-Codes-Suche";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "Suche nach Open Location Codes (Plus codes)";
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
            'target' => null,
        );
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $configuration = parent::getConfiguration();
        return $configuration;
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbOlcSearch';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\AlkisBundle\Element\Type\OlcSearchAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderAlkisBundle:ElementAdmin:olcsearch.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array('mapbender.element.olcsearch.js',
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                '@FOMCoreBundle/Resources/public/js/widgets/dropdown.js'),
            'css' => array(
                '@MapbenderAlkisBundle/Resources/public/sass/element/mapbender.element.olcsearch.scss',
                '@MapbenderAlkisBundle/Resources/public/sass/element/mapbender.element.olcsearch.result.scss')
        );
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
            ->render(
                'MapbenderAlkisBundle:Element:olcsearch.html.twig',
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

    protected function search()
    {
        // benötigte Parameter einlesen
        $term = $this->container->get('request')->get('term', null);
        $epsg_in = $this->container->get('request')->get('epsg_in', $conf['default_epsg_in']);
        
        // Konfiguration einlesen
        $conf = $this->container->getParameter('olc');
        
        // Suche durchführen mittels cURL
        $curl = curl_init();
        $term = curl_escape($curl, $term);
        $url = $conf['url'] . 'query=' . $term . '&epsg_out=' . $conf['epsg_out'] . '&epsg_in=' . $epsg_in;
        curl_setopt($curl, CURLOPT_URL, $url); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        
        // Suchresultat verarbeiten
        $json = json_decode(curl_exec($curl), true);
        curl_close($curl);
        $minx = $json['geometry']['coordinates'][0][0][0];
        $miny = $json['geometry']['coordinates'][0][0][1];
        $maxx = $json['geometry']['coordinates'][0][2][0];
        $maxy = $json['geometry']['coordinates'][0][2][1];
        
        // Geometrien in WKT umwandeln
        switch ($json['properties']['level']) {
            case 3:
                $deltax1 = 232.1;
                $deltax2 = 228.2;
                $deltay = 135.3;
                break;
            case 4:
                $deltax1 = 11.4;
                $deltax2 = 11.4;
                $deltay = 6.75;
                break;
            case 5:
                $deltax1 = 0.6;
                $deltax2 = 0.6;
                $deltay = 0.33;
                break;
        }
        $wkt = 'POLYGON ((' . $minx . ' ' . $miny . ', ' . ($maxx - $deltax2) . ' ' . ($miny - $deltay) . ', ' . $maxx . ' ' . $maxy . ', ' . ($minx + $deltax1) . ' ' . ($maxy + $deltay) . ', ' . $minx . ' ' . $miny . '))';
        
        // Suchresultat innerhalb in Konfiguration definierter Bounding-Box?
        if ($minx >= $conf['minx'] && $maxx <= $conf['maxx'] && $miny >= $conf['miny'] && $maxy <= $conf['maxy'])
            $innerhalb = true;
        
        // Übergabe des Suchresultats an Template
        $html = $this->container->get('templating')->render(
            'MapbenderAlkisBundle:Element:resultsolc.html.twig',
            array(
                'result' => $json,
                'innerhalb' => $innerhalb,
                'wkt' => $wkt
            )
        );

        return new Response($html, 200, array('Content-Type' => 'text/html'));
    }
}
