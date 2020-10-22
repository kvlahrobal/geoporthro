<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\SourceMetadata;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsLayerSource;

/**
 * 
 * @inheritdoc
 * @author Paul Schmidt
 */
class WmsMetadata extends SourceMetadata
{

    protected $instance;

    public function __construct(WmsInstance $instance)
    {
        parent::__construct();
        $this->instance = $instance;
    }
    
    private function prepareData($itemName)
    {
        $src = $this->instance->getSource();
        
        # eigene WMS
        if (strpos(SourceMetadata::getNotNull($src->getOriginUrl()), "geo.sv.rostock.de") !== false) {
            # mit Parametern
            if (strpos(SourceMetadata::getNotNull($src->getOriginUrl()), "?") !== false) {
                # Parameter entfernen
                $originUrl = substr(SourceMetadata::getNotNull($src->getOriginUrl()), 0, strpos(SourceMetadata::getNotNull($src->getOriginUrl()), "?"));
            }
            # ohne Parameter
            else {
                # URL unverändert übernehmen
                $originUrl = SourceMetadata::getNotNull($src->getOriginUrl());
            }
            # Verbindung zur Datenbank von Geolotse.HRO aufbauen
            $connection = pg_connect("host=dbnode10.sv.rostock.de dbname=geolotse user=admin password=hro62.15;:_");
            # verbundene URLs holen
            pg_prepare("", "SELECT CASE WHEN \"group\" = 'WMS' THEN '1' || \"group\" WHEN \"group\" = 'WFS' THEN '3' || \"group\" WHEN \"group\" = 'INSPIRE View Service' THEN '5' || \"group\" WHEN \"group\" = 'INSPIRE Download Service' THEN '7' || \"group\" ELSE \"group\" END AS typ, link FROM links WHERE category = 'geoservice' AND parent_id in (SELECT parent_id FROM links WHERE link = '$originUrl') UNION SELECT DISTINCT CASE WHEN s.target = 'metadata' AND l1.\"group\" = 'INSPIRE Download Service' THEN '8metadata_dls' WHEN s.target = 'metadata' AND l1.\"group\" = 'INSPIRE View Service' THEN '6metadata_vs' WHEN s.target = 'metadata' AND l1.\"group\" = 'WFS' THEN '4metadata_wfs' WHEN s.target = 'metadata' AND l1.\"group\" = 'WMS' THEN '2metadata_wms' ELSE s.target END AS typ, s.link FROM sublinks s, links_sublinks ls, links l1, links l2 WHERE s.target IN ('metadata', 'opendata') AND s.id = ls.sublink_id AND (ls.link_id = l1.id OR ls.link_id = l1.parent_id) AND l1.parent_id = l2.parent_id AND l2.link = '$originUrl' ORDER BY 1, 2");
            $result = pg_execute("", array());
            $urls = array();
            while ($row = pg_fetch_assoc($result)) {
                $urls[] = array('typ' => $row['typ'], 'link' => $row['link']);
            }
            # Beschreibung holen
            pg_prepare("", "SELECT description FROM links WHERE link = '$originUrl' LIMIT 1");
            $result = pg_execute("", array());
            while ($row = pg_fetch_assoc($result)) {
                $description = $row['description'];
            }
            # Titel holen
            pg_prepare("", "SELECT title FROM links WHERE link = '$originUrl' LIMIT 1");
            $result = pg_execute("", array());
            while ($row = pg_fetch_assoc($result)) {
                $title = $row['title'];
            }
            pg_close($connection);
        }
        # andere WMS
        else {
            # mit Parametern
            if (strpos(SourceMetadata::getNotNull($src->getOriginUrl()), "apabilitie") !== false) {
                # URL unverändert übernehmen
                $originUrl = SourceMetadata::getNotNull($src->getOriginUrl());
            }
            # ohne Parameter
            else {
                # bei UMN-MapServer-URL
                if (strpos(SourceMetadata::getNotNull($src->getOriginUrl()), "map=") !== false) {
                    # Parameter hinzufügen
                    $originUrl = SourceMetadata::getNotNull($src->getOriginUrl()) . "&service=WMS&version=1.3.0&request=GetCapabilities";
                }
                # ansonsten
                else {
                    # Parameter hinzufügen
                    $originUrl = SourceMetadata::getNotNull($src->getOriginUrl()) . "?service=WMS&version=1.3.0&request=GetCapabilities";
                }
            }
        }
        
        if ($this->getUseCommon()) {
            $source_items = array();
            if ($title)
                $source_items[] = array("title" => $title);
            else
                $source_items[] = array("title" => SourceMetadata::getNotNull($src->getTitle()));
            if ($description)
                $source_items[] = array("description" => $description);
            else
                $source_items[] = array("description" => SourceMetadata::getNotNull($src->getDescription()));
            if ($urls) {
                foreach ($urls as $url) {
                    if ($url['typ'] === 'GeoRSS') {
                        $source_items[] = array("georssUrl" => $url['link']);
                    } elseif ($url['typ'] === '7INSPIRE Download Service') {
                        $source_items[] = array("dlsUrl" => $url['link']);
                    } elseif ($url['typ'] === '5INSPIRE View Service') {
                        $source_items[] = array("vsUrl" => $url['link']);
                    } elseif ($url['typ'] === 'SOS') {
                        $source_items[] = array("sosUrl" => $url['link']);
                    } elseif ($url['typ'] === 'TMS') {
                        $source_items[] = array("tmsUrl" => $url['link']);
                    } elseif ($url['typ'] === 'WCS') {
                        $source_items[] = array("wcsUrl" => $url['link']);
                    } elseif ($url['typ'] === '3WFS') {
                        $source_items[] = array("wfsUrl" => $url['link']);
                    } elseif ($url['typ'] === '1WMS') {
                        $source_items[] = array("wmsUrl" => $url['link']);
                    } elseif ($url['typ'] === 'WMS-C') {
                        $source_items[] = array("wmscUrl" => $url['link']);
                    } elseif ($url['typ'] === 'WMTS') {
                        $source_items[] = array("wmtsUrl" => $url['link']);
                    } elseif ($url['typ'] === 'opendata') {
                        $source_items[] = array("opendataUrl" => $url['link']);
                    } /*elseif ($url['typ'] === '8metadata_dls') {
                        $source_items[] = array("metadataDlsUrl" => $url['link']);
                    } elseif ($url['typ'] === '6metadata_vs') {
                        $source_items[] = array("metadataVsUrl" => $url['link']);
                    } elseif ($url['typ'] === '4metadata_wfs') {
                        $source_items[] = array("metadataWfsUrl" => $url['link']);
                    } elseif ($url['typ'] === '2metadata_wms') {
                        $source_items[] = array("metadataWmsUrl" => $url['link']);
                    }*/
                }
            } else {
                $source_items[] = array("wmsUrl" => $originUrl);
            }
            $this->addMetadataSection(SourceMetadata::$SECTION_COMMON, $source_items);
        }
        
        if ($this->getUseUseConditions()) {
            $tou_items = array();
            $tou_items[] = array("accessconstraints" => SourceMetadata::getNotNull($src->getAccessConstraints()));
            $this->addMetadataSection(SourceMetadata::$SECTION_USECONDITIONS, $tou_items);
        }

        if ($this->getUseItems() && $itemName !== '') {
            $layer = null;
            foreach ($this->instance->getLayers() as $layerH) {
                if ($layerH->getSourceItem()->getName() === $itemName) {
                    $layer = $layerH;
                    break;
                }
            }
            $layer_items = array();
            if ($layer) {
                $layer_items = $this->prepareLayers($layer);
            }
            $this->addMetadataSection(SourceMetadata::$SECTION_ITEMS, $layer_items);
        }
        
        if ($this->getUseContact()) {
            $contact = $src->getContact();
            $contact_items = array();
            $contact_items[] = array("organization" => SourceMetadata::getNotNull($contact->getOrganization()));
            $contact_items[] = array("voiceTelephone" => SourceMetadata::getNotNull($contact->getVoiceTelephone()));
            $contact_items[] = array("facsimileTelephone" => SourceMetadata::getNotNull($contact->getFacsimileTelephone()));
            $contact_items[] = array("electronicMailAddress" => SourceMetadata::getNotNull($contact->getElectronicMailAddress()));
            $contact_items[] = array("address" => SourceMetadata::getNotNull($contact->getAddress()));
            $contact_items[] = array("addressPostCode" => SourceMetadata::getNotNull($contact->getAddressPostCode()));
            $contact_items[] = array("addressCity" => SourceMetadata::getNotNull($contact->getAddressCity()));
            $this->addMetadataSection(SourceMetadata::$SECTION_CONTACT, $contact_items);
        }
    }
    
    private function prepareLayers($layer)
    {
        $layer_items = array();
        $layer_items[] = array("title" => SourceMetadata::getNotNull($layer->getSourceItem()->getTitle()));
        $layer_items[] = array("description" => SourceMetadata::getNotNull($layer->getSourceItem()->getAbstract()));
        $name = SourceMetadata::getNotNull($layer->getSourceItem()->getName());

        # Metadatenbank
        $connection = pg_connect("host=dbnode10.sv.rostock.de dbname=geodaten user=lesen password=selen");
        $src = $this->instance->getSource();
        $title = SourceMetadata::getNotNull($src->getTitle());
        $title = str_replace("Hansestadt Rostock", "Hanse- und Universitätsstadt Rostock", $title);
        pg_prepare("", "SELECT rf, currentness_data, source_place_data, source_data, source_mail_data FROM (SELECT 1 AS rf, CASE WHEN aktualisierung_datenquelle IS TRUE AND aktualisierung_datenquelle_intervall IS NULL THEN to_char(now(), 'DD.MM.YYYY') ELSE to_char(stand_datenquelle, 'DD.MM.YYYY') END AS currentness_data, CASE WHEN autorenschaft_stelle IS NOT NULL THEN array_to_string(string_to_array(autorenschaft_stelle, '~'), '~') WHEN ckan_autor_stelle IS NOT NULL THEN ckan_autor_stelle ELSE 'Hanse- und Universitätsstadt Rostock' END AS source_place_data, regexp_replace(autoren, '\,', '~', 'g') AS source_data, autoren_email AS source_mail_data FROM metadatenpflege.metadaten WHERE mapfile_layer = '$name' OR titel_lang = '$title' OR 'ALKIS-' || regexp_replace(titel_lang, ' aus dem Amtlichen Liegenschaftskatasterinformationssystem \(ALKIS\)', '') = '$title' OR 'ALKIS' || regexp_replace(titel_lang, '^Amtliches Liegenschaftskatasterinformationssystem \(ALKIS\)', '') = '$title' UNION SELECT 2 AS rf, CASE WHEN aktualisierung_datenquelle IS TRUE AND aktualisierung_datenquelle_intervall IS NULL THEN to_char(now(), 'DD.MM.YYYY') ELSE to_char(stand_datenquelle, 'DD.MM.YYYY') END AS currentness_data, CASE WHEN autorenschaft_stelle IS NOT NULL THEN array_to_string(string_to_array(autorenschaft_stelle, '~'), '~') WHEN ckan_autor_stelle IS NOT NULL THEN ckan_autor_stelle ELSE 'Hanse- und Universitätsstadt Rostock' END AS source_place_data, regexp_replace(autoren, '\,', '~', 'g') AS source_data, autoren_email AS source_mail_data FROM metadatenpflege.metadaten WHERE mapfile_name = regexp_replace(substring('$name', '\..*\.'), '\.', '', 'g') OR datenquelle ~ regexp_replace(substring('$name', '\..*\.'), '\.', '', 'g')) AS tabelle ORDER BY rf LIMIT 1");
        $result = pg_execute("", array());
        while ($row = pg_fetch_assoc($result)) {
            $currentnessData = $row["currentness_data"];
            $sourcePlaceData = $row["source_place_data"];
            $sourceData = $row["source_data"];
            $sourceMailData = $row["source_mail_data"];
        }
        pg_close($connection);
        
        if ($currentnessData) {
            if($layer->getSublayer()->count() == 0) {
                $layer_items[] = array("currentnessData" => $currentnessData);
            }
        }
        
        if ($sourcePlaceData) {
            if($layer->getSublayer()->count() == 0) {
                $layer_items[] = array("sourcePlaceData" => $sourcePlaceData);
            }
        }
        
        if ($sourceData) {
            if($layer->getSublayer()->count() == 0) {
                $layer_items[] = array("sourceData" => $sourceData);
            }
        }
        
        if ($sourceMailData) {
            if($layer->getSublayer()->count() == 0) {
                $layer_items[] = array("sourceMailData" => $sourceMailData);
            }
        }
        
        if($layer->getSublayer()->count() > 0) {
            $sublayers = array();
            foreach($layer->getSublayer() as $sublayer){
                $sublayers[] = $this->prepareLayers($sublayer);
            }
            $layer_items[] = array(SourceMetadata::$SECTION_SUBITEMS => $sublayers);
        }
        return $layer_items;
    }

    /**
     * @inheritdoc
     */
    public function render($templating, $itemName = null)
    {
        $this->prepareData($itemName);
        $content = $templating->render('MapbenderCoreBundle::metadata.html.twig',
            array('metadata' => $this->data, 'prefix' => 'mb.wms.metadata.section.'));
        return $content;
    }

}
