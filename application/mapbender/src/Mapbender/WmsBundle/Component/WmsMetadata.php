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
        $title = SourceMetadata::getNotNull($src->getTitle());
        
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
            # WCS- und WFS-URL definieren
            $wcsUrl = substr($originUrl, 0, strpos($originUrl, "wms")) . "wcs";
            $wcsUrlHeaders = @get_headers($wcsUrl . "?service=WCS&version=2.0.0&request=GetCapabilities");
            $wfsUrl = substr($originUrl, 0, strpos($originUrl, "wms")) . "wfs";
            $wfsUrlHeaders = @get_headers($wfsUrl . "?service=WFS&version=1.1.0&request=GetCapabilities");
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
        
        # Metadatenbank
        $title = str_replace("Hansestadt Rostock", "Hanse- und Universitätsstadt Rostock", $title);
        $connection = pg_connect("host=dbnode10.sv.rostock.de dbname=geodaten user=lesen password=selen");
        pg_prepare("", "SELECT ckan_datensatz_id AS ckan_id FROM metadatenpflege.metadaten WHERE mapfile_layer = '$name' OR mapfile_name = regexp_replace(substring('$name', '\..*\.'), '\.', '', 'g') OR datenquelle ~ regexp_replace(substring('$name', '\..*\.'), '\.', '', 'g') OR titel_lang = '$title' OR 'ALKIS-' || regexp_replace(titel_lang, ' aus dem Amtlichen Liegenschaftskatasterinformationssystem \(ALKIS\)', '') = '$title' OR 'ALKIS' || regexp_replace(titel_lang, '^Amtliches Liegenschaftskatasterinformationssystem \(ALKIS\)', '') = '$title' LIMIT 1");
        $result = pg_execute("", array());
        pg_close($connection);
        while ($row = pg_fetch_assoc($result)) {
            $ckanId = $row["ckan_id"];
        }
        
        if ($this->getUseCommon()) {
            $source_items = array();
            $source_items[] = array("title" => $title);
            $source_items[] = array("originUrl" => $originUrl);
            if ($wcsUrlHeaders && $wcsUrlHeaders[0] == 'HTTP/1.1 200 OK') {
                $source_items[] = array("wcsUrl" => $wcsUrl);
            }
            if ($wfsUrlHeaders && $wfsUrlHeaders[0] == 'HTTP/1.1 200 OK') {
                $source_items[] = array("wfsUrl" => $wfsUrl);
            }
            if ($ckanId) {
                $source_items[] = array("ckanId" => $ckanId);
            }
            $source_items[] = array("description" => SourceMetadata::getNotNull($src->getDescription()));
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
        pg_prepare("", "SELECT CASE WHEN aktualisierung_datenquelle IS TRUE AND aktualisierung_datenquelle_intervall IS NULL THEN to_char(now(), 'DD.MM.YYYY') ELSE to_char(stand_datenquelle, 'DD.MM.YYYY') END AS currentness_data, CASE WHEN autorenschaft_stelle IS NOT NULL THEN array_to_string(string_to_array(autorenschaft_stelle, '~'), '~') WHEN ckan_autor_stelle IS NOT NULL THEN ckan_autor_stelle ELSE 'Hanse- und Universitätsstadt Rostock' END AS source_place_data, regexp_replace(autoren, '\,', '~', 'g') AS source_data, autoren_email AS source_mail_data FROM metadatenpflege.metadaten WHERE mapfile_layer = '$name' OR mapfile_name = regexp_replace(substring('$name', '\..*\.'), '\.', '', 'g') OR datenquelle ~ regexp_replace(substring('$name', '\..*\.'), '\.', '', 'g') OR titel_lang = '$title' OR 'ALKIS-' || regexp_replace(titel_lang, ' aus dem Amtlichen Liegenschaftskatasterinformationssystem \(ALKIS\)', '') = '$title' OR 'ALKIS' || regexp_replace(titel_lang, '^Amtliches Liegenschaftskatasterinformationssystem \(ALKIS\)', '') = '$title' LIMIT 1");
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
