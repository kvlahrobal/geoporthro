<?php

namespace Mapbender\AlkisBundle\Element;

use ARP\SolrClient2\SolrClient;
use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * AlkisInfo element
 *
 * This element will provide feature info for most layer types
 *
 * @author Paul Schmidt
 */
class AlkisInfo extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "AlkisInfo";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "AlkisInfo Description";
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
            'tooltip' => 'Alkis Info Dialog',
            'type' => 'dialog',
            'buffer' => 1.0,
            'options' => array(),
            "target" => null,
            "width" => 700,
            "height" => 600
            );
    }

    public function getConfiguration()
    {
        $options = parent::getConfiguration();
        $slug = $this->application->getEntity()->getSlug();
        $id = $this->getEntity()->getId();
        $url = $this->container->get('router')
            ->generate(
                'mapbender_alkis_alkis_script',
                array('slug' => $slug,
                      'id' => $id,
                      'action' => 'info',
                      'script' => 'alkisfsnw',
                      'extension' => 'php'
                    )
            );
        $infourleigen = $this->container->get('router')
            ->generate(
                'mapbender_alkis_alkis_script',
                array('slug' => $slug,
                      'id' => $id,
                      'action' => 'info',
                      'script' => 'alkisnamstruk',
                      'extension' => 'php'
                    )
            );
        $infourlgrund = $this->container->get('router')
            ->generate(
                'mapbender_alkis_alkis_script',
                array('slug' => $slug,
                      'id' => $id,
                      'action' => 'info',
                      'script' => 'alkisbestnw',
                      'extension' => 'php'
                    )
            );
        $options['infourl'] = $url;
        $options['infourleigen'] = $infourleigen;
        $options['infourlgrund'] = $infourlgrund;
        $solr = $this->container->getParameter('solr');
        $options['dataSrs'] = $solr['srs'];
        $options['spatialSearchSrs'] = "EPSG:4326";
        return $options;
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbAlkisInfo';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\AlkisBundle\Element\Type\AlkisInfoAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function listAssets()
    {
        return array(
            'js' => array(
                'mapbender.element.alkisinfo.js',
                'mapbender.container.info.js', // TODO remove, if this file added into MapbenderCore
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js'),
            'css' => array(
                '@MapbenderAlkisBundle/Resources/public/sass/element/mapbender.element.alkisinfo.scss'
            ),
            'trans' => array()
        );
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        $configuration = parent::getConfiguration();
        return $this->container->get('templating')
                ->render(
                    'MapbenderAlkisBundle:Element:alkisinfo.html.twig',
                    array(
                    'id' => $this->getId(),
                    'configuration' => $configuration,
                    'title' => $this->getTitle()
                    )
                );
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderAlkisBundle:ElementAdmin:alkisinfo.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
        switch ($action) {
            case 'search':
                return $this->search();
            case 'info':
                $script = $this->container->get('request')->get("__script__", null);
                if ($script) {
                    return $this->getInfo($script);
                } else {
                    throw new NotFoundHttpException('No such script parameter');
                }
            default:
                throw new NotFoundHttpException('No such action');
        }
    }

    protected function search()
    {
        $geom = $this->container->get('request')->get("geom", null);
        $srs = $this->container->get('request')->get("srs", '');
        $gmlId = $this->container->get('request')->get("gmlid");

        $term = $this->container->get('request')->get("term", '');
        $page = $this->container->get('request')->get("page", 1);
        $type = $this->container->get('request')->get("type", 'flur');

        $solr = new SolrClient(
            $this->container->getParameter('solr')
        );
        $options = $this->getConfiguration();

        if (!is_null($geom) && $srs === $options['spatialSearchSrs']) {
            $result = $solr
                ->wildcardMinStrlen(1)
                ->page($page)
                ->where("geom", 'Intersects(' . $geom . ')')
                ->find($term);
            return new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
        } elseif (!is_null($gmlId)) {
            $result = $solr
                ->wildcardMinStrlen(1)
                ->page($page)
                ->where('gmlid', $gmlId)
                ->find();
            return new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
        }
    }

    protected function getInfo($scriptName)
    {
        $options = $this->getConfiguration();
        if (isset($options['secured']) && $options['secured']) {
            $alkisLocation = $this->container->get('kernel')->getRootDir() . '/../external/alkis/info/alkis/';
            $this->addInfoSecured($alkisLocation, $scriptName);
        } else {
            $alkisLocation = $this->container->get('kernel')->getRootDir() . '/../external/alkis/info_light/alkis/';
            $this->addInfoContent($alkisLocation, $scriptName);
        }
        return new Response("", 200, array('Content-Type' => 'text/html'));
    }

    private function addInfoSecured($alkisLocation, $scriptName)
    {
        // Schema und Name der Datenbanktabelle für das Logging der ALKIS-Auskunft identifizieren
        $schema = $this->container->getParameter('hro_log_database_schema');
        $table = $this->container->getParameter('hro_log_database_table');
        
        // Verbindung zur Datenbank für das Logging der ALKIS-Auskunft öffnen
        $conn = $this->container->get('doctrine.dbal.hro_log_data_connection');
        $queryBuilder = $conn->createQueryBuilder();
        
        // für Log-Eintrag: Benutzer identifizieren
        $user = $this->container->get('security.context')->getUser()->getUsername();
        
        // für Log-Eintrag: Vorgang identifizieren
        $map = array(
            'alkisfsnw.php' => 'Flurstück',
            'alkisfshist.php' => 'Flurstückshistorie',
            'alkisgebaeudenw.php' => 'Gebäude',
            'alkislage.php' => 'Lage',
            'alkisstrasse.php' => 'Straße',
            'alkisbestnw.php' => 'Grundbuchblatt',
            'alkisnamstruk.php' => 'Eigentümer',
        );
        $event = isset($map[$scriptName]) ? $map[$scriptName] : 'nicht definiert';
        
        // für Log-Eintrag: ALKIS-ID (GML-ID) identifizieren
        $id = $this->container->get('request')->get('gmlid');
        
        // für Log-Eintrag: Zeitpunkt identifizieren
        date_default_timezone_set('Europe/Berlin');
        $date = date('Y-m-d');
        $time = date('H:i:s');
        $timestamp = $date . ' ' . $time;

        // Log-Eintrag in Datenbanktabelle schreiben
        $conn->insert($schema . '.' . $table, array('benutzer' => $user, 'vorgang' => $event, 'alkis_id' => $id, 'zeitpunkt' => $timestamp));
        
        // Verbindung zur Datenbank für das Logging der ALKIS-Auskunft schließen
        $conn->close();
        
        // ALKIS-Auskunft starten
        $this->addInfoContent($alkisLocation, $scriptName);
    }

    private function addInfoContent($alkisLocation, $scriptName)
    {
        global $debug, $gkz, $idumschalter, $idanzeige, $showkey, $hilfeurl, $con;
        require_once $alkisLocation . 'alkis_conf_location.php';
        $dbhost = $this->container->getParameter('hro_database_host');
        $dbport = $this->container->getParameter('hro_database_port');
        $dbname = $this->container->getParameter('hro_database_name');
        $dbuser = $this->container->getParameter('hro_database_user');
        $dbpass = $this->container->getParameter('hro_database_password');
        $dbpre = $this->container->getParameter('hro_database_pre');
        $dbvers = $this->container->getParameter('hro_database_vers');

        $user = $this->container->get('security.context')->getUser();

        $multiadress = 'j';
        $i = 0;

        $_SESSION["mb_user_description"] = '';
        $_SESSION["mb_user_name"] = $user->getUsername();

        /* Entwicklung / Produktion */
        $idumschalter = false;
        /* Authentifizierung $auth="mapbender"; */
        $auth = "";  // ** temporaer deaktiviert **!
//        $mapbender = "/data/mapwww/http/php/mb_validateSession.php";
        /* Link für Hilfe */
        $hilfeurl = 'http://map.krz.de/mapwww/?Themen:ALKIS';
        /* Entwicklungsumgebung */
        $debug = 0; // 0=Produktion, 1=mit Fehlermeldungen,

        $keys = isset($_GET["showkey"]) ? $_GET["showkey"] : "n";
        if ($keys == "j") {
            $showkey = true;
        } else {
            $showkey = false;
        }
        $id = false;
        include $alkisLocation . $scriptName;
    }
}
