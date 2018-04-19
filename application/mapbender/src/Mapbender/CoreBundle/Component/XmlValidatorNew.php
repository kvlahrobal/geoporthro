<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use OwsProxy3\CoreBundle\Component\CommonProxy;
use OwsProxy3\CoreBundle\Component\ProxyQuery;

/**
 * XmlValidator class to validate xml documents.
 *
 * @author Paul Schmidt
 */
class XmlValidatorNew
{
    /**
     *
     * @var type container
     */
    protected $container;

    /**
     * @var string path to local directory for schemas, document type definitions.
     */
    protected $dir = null;

    /**
     * @var array Proxy connection parameters
     */
    protected $proxy_config;

    /**
     * Creates an instance.
     * @var array temp files to delete
     */
    protected $filesToDelete;
    protected $schemaLocations;
    protected $addedResources;

    public function __construct($container, array $proxy_config, $orderFromWeb = null)
    {
        $this->container = $container;
        if ($orderFromWeb) {
            $this->dir = $this->createDir(
                $this->normalizePath(
                    $this->container->get('kernel')->getRootDir() . '/../web/' . $orderFromWeb,
                    DIRECTORY_SEPARATOR
                )
            );
        }
        $this->proxy_config  = $proxy_config;
        $this->filesToDelete = array();
    }

    private function getCompactSchema($imports)
    {
/*
        $imps = '';
        foreach ($imports as $ns => $loc) {
            $imps .= '<xsd:import namespace="' . $ns . '" schemaLocation="' . $loc . '" />\n';
        }
        $schemaStr            = '<?xml version="1.0" encoding="utf-8" ?>\n
//            <xsd:schema xmlns:xsd="http://www.w3.org/2001/XMLSchema" elementFormDefault="qualified">\n
//            <xsd:import namespace="http://www.w3.org/XML/1998/namespace"/>\n' .
//            $imps .
//            '</xsd:schema>';
//        $schema   = new \DOMDocument();
//        $schema->loadXML($schemaStr);
//        return $schema;
*/

        $imps = '';
        foreach ($imports as $ns => $loc) {
            $imps .= sprintf('<xsd:import namespace="%s" schemaLocation="%s" />', $ns, $loc);
        }
        return <<<EOF
<?xml version="1.0" encoding="utf-8" ?>
<xsd:schema xmlns:xsd="http://www.w3.org/2001/XMLSchema" elementFormDefault="qualified">
<xsd:import namespace="http://www.w3.org/XML/1998/namespace"/>
$imps
</xsd:schema>
EOF
        ;
    }

    /**
     * Validates a xml document.
     * @param \DOMDocument $doc a xml dicument
     * @return \DOMDocument the validated xml document
     * @throws \Exception
     * @throws XmlParseException
     */
    public function validate(\DOMDocument $doc)
    {
        $this->filesToDelete = array();
        if (isset($doc->doctype)) {// DTD
            $docH     = new \DOMDocument();
            $filePath = $this->getFileName($doc->doctype->name, $doc->doctype->systemId);
            $this->isDirExists($filePath);
            if (!is_file($filePath)) {
                $proxy_query = ProxyQuery::createFromUrl($doc->doctype->systemId);
                $proxy       = new CommonProxy($this->proxy_config, $proxy_query);
                try {
                    $browserResponse = $proxy->handle();
                    $content         = $browserResponse->getContent();
                    file_put_contents($filePath, $content);
                } catch (\Exception $e) {
                    $this->removeFiles();
                    throw $e;
                }
            }
            $docStr = str_replace($doc->doctype->systemId, $this->addFileSchema($filePath), $doc->saveXML());
            $doc->loadXML($docStr);
            unset($docStr);
            if (!@$docH->loadXML($doc->saveXML(), LIBXML_DTDLOAD | LIBXML_DTDVALID)) {
                $this->removeFiles();
                throw new XmlParseException("mb.wms.repository.parser.couldnotparse");
            }
            $doc = $docH;
            if (!@$doc->validate()) { // check with DTD
                $this->removeFiles();
                throw new XmlParseException("mb.wms.repository.parser.not_valid_dtd");
            }
        } else {
            $schemaLocations = $this->addSchemas($doc);
            $imports = "";

//            foreach ($schemaLocations as $item) {
//                if (isset($item['import'])) {
//                    $imports .= '<xsd:import namespace="' . $item['import']['namespace']
//                        . '" schemaLocation="' . $item['import']['location'] . '" />\n';
//                }
//            }

            foreach ($schemaLocations as $namespace => $location) {
                $imports .=
                    sprintf('<xsd:import namespace="%s" schemaLocation="%s" />' . "\n", $namespace, $location);
            }

            $source = <<<EOF
<?xml version="1.0" encoding="utf-8" ?>
<xsd:schema xmlns:xsd="http://www.w3.org/2001/XMLSchema" elementFormDefault="qualified">
<xsd:import namespace="http://www.w3.org/XML/1998/namespace"/>
$imports
</xsd:schema>
EOF
            ;
            libxml_use_internal_errors(true);
            libxml_clear_errors();
            $valid = @$doc->schemaValidateSource($source);



//            $schemaLocations = $this->addSchemas($doc);
//            $schema = $this->getCompactSchema($schemaLocations);
//
////            $this->resultSchema->save('/home/paul/temp/HRO/schema.xsd');
//            libxml_use_internal_errors(true);
//            libxml_clear_errors();
            try {
                $valid = @$doc->schemaValidateSource($schema);
                if (!$valid) {
                    $errors  = libxml_get_errors();
                    $message = "";
                    foreach ($errors as $error) {
                        $message .= "\n" . $error->message;
                    }
                    $this->container->get('logger')->err($message);
                    libxml_clear_errors();
                    $this->removeFiles();
                    throw new XmlParseException("mb.wms.repository.parser.not_valid_xsd");
                }
                libxml_clear_errors();
            } catch (XmlParseException $e) {
                throw $e;
            } catch (\Exception $e) {
                $this->removeFiles();
                throw $e;
            }
        }
        $this->removeFiles();
        return $doc;
    }

    /**
     * Returns namespaces and locations as array.
     * @param \DOMDocument $doc
     * @return array schema locations
     */
    private function addSchemas(\DOMDocument $doc)
    {
        $element = $doc->documentElement->getAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'schemaLocation');
        if ($element) {
            $this->addedResources = array();
            $items                = preg_split('/\s+/', $element);
            for ($i = 1, $nb = count($items); $i < $nb; $i += 2) {
                $temp = $this->addSchemaLocation($items[$i - 1], $items[$i]);
                if ($temp) {
                    $fullFileName = $this->getFileName($items[$i - 1], $items[$i]);
                    $schemaLocations[$items[$i - 1]] = $this->addFileSchema($fullFileName);
                }
            }
        }
        return $schemaLocations;
    }

    /**
     * Returns namespaces and locations as array.
     * @param \DOMDocument $doc
     * @return array schema locations
     */
    private function getResultSchema(\DOMDocument $doc)
    {
        $element = $doc->documentElement->getAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'schemaLocation');
        if ($element) {
            $this->addedResources = array();
            $resultSchema         = null;
            $items                = preg_split('/\s+/', $element);
            for ($i = 1, $nb = count($items); $i < $nb; $i += 2) {
                $temp = $this->addSchemaLocation($items[$i - 1], $items[$i]);
                if ($temp) {
                    if (!$resultSchema) {
                        $resultSchema = $temp;
                    } else {
                        foreach ($temp->documentElement->childNodes as $elm) {
                            if ($elm->nodeType == XML_ELEMENT_NODE) {
                                $imp = $resultSchema->importNode($elm, true);
                                $resultSchema->documentElement->appendChild($imp);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Adds namespace and location to schema location array.
     * @param string $ns namespace
     * @param string $path url
     * @return \DOMDocument schema
     */
    private function addSchemaLocation($ns, $url)
    {
        $fullFileName = $this->getFileName($ns, $url);
        if (in_array($fullFileName, $this->addedResources)) {
            return null;
        }
        if (!is_file($fullFileName)) {
            $doc = $this->loadRemote($url);
            $this->saveRemote($ns, $url, $doc);
        } else {
            $doc = $this->loadLocal($fullFileName);
        }
        $this->addedResources[] = $fullFileName;
        $root                   = $doc->documentElement;
        foreach ($root->childNodes as $child) {
            if ($child->nodeType == XML_ELEMENT_NODE) {
                if ($child->localName === "import") {
                    $ns_     = $child->getAttribute("namespace");
                    $sl_     = $child->getAttribute("schemaLocation");
                    $doc_imp = null;
                    if (isset($sl_url['host'])) {
                        $doc_imp = $this->addSchemaLocation($ns_, $sl_);
                    } else {
                        $doc_imp = $this->addSchemaLocation($ns_, $sl_, strpos($sl_, '../') === 0 ? $sl_ : '../' . $sl_);
                    }
                    if ($doc_imp) {
                        foreach ($doc_imp->documentElement->childNodes as $elm) {
                            if ($elm->nodeType == XML_ELEMENT_NODE) {
                                $imp = $doc->importNode($elm, true);
                                $root->insertBefore($imp, $child);
                            }
                        }
                    }
                    $root->removeChild($child);
                    $this->saveRemote($ns, $url, $doc);
                } elseif ($child->localName === "include") {
                    $sl_     = $include->getAttribute("schemaLocation");
                    $ns_     = $root->getAttribute("targetNamespace");
                    $sl_url  = parse_url($sl_);
                    $doc_imp = null;
                    if (isset($sl_url['host'])) {
                        $doc_imp = $this->addSchemaLocation($ns_, $sl_);
                    } else {
                        $doc_imp = $this->addSchemaLocation($ns_, $sl_, strpos($sl_, '../') === 0 ? $sl_ : '../' . $sl_);
                    }
                    if ($doc_imp) {
                        foreach ($doc_imp->documentElement->childNodes as $elm) {
                            if ($elm->nodeType == XML_ELEMENT_NODE) {
                                $imp = $doc->importNode($elm, true);
                                $root->insertBefore($imp, $child);
                            }
                        }
                    }
                    $root->removeChild($child);
                    $this->saveRemote($ns, $url, $doc);
                } else {
                    $a = 0;
                }
            }
        }
        return $doc;
    }

    /**
     * Loads an external xml schema, saves it local and adds a local path into a schemaLocation.
     * @param string $ns namespace
     * @param string $url path or url
     * @throws \Exception  create exception
     * @throws XmlParseException xml parse exception
     */
    private function loadRemote($url)
    {
        $proxy_query = ProxyQuery::createFromUrl($url);
        $proxy       = new CommonProxy($this->proxy_config, $proxy_query);
        try {
            $browserResponse = $proxy->handle();
            $content         = $browserResponse->getContent();
            $doc             = new \DOMDocument();
            if (!@$doc->loadXML($content)) {
                throw new XmlParseException("mb.core.xmlvalidator.couldnotcreate");
            }
            return $doc;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Loads an external xml schema, saves it local and adds a local path into a schemaLocation.
     * @param string $ns namespace
     * @param string $url path or url
     * @throws \Exception  create exception
     * @throws XmlParseException xml parse exception
     */
    private function saveRemote($ns, $url, $document)
    {
        $fullFileName = $this->getFileName($ns, $url);
        $this->isDirExists($fullFileName);
        $document->save($fullFileName);
    }

    private function loadLocal($filePath)
    {
        try {
            $doc = new \DOMDocument();
            if (!@$doc->load($filePath)) {
                throw new XmlParseException("mb.core.xmlvalidator.couldnotcreate");
            }
            return $doc;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Generates a file path
     * @param string $ns namespace
     * @param string $url url
     * @return string file path
     */
    private function getFileName($ns, $url)
    {
        if ($this->dir === null) {
            $tmpfile               = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mb3_" . time();
            $this->filesToDelete[] = $tmpfile;
            return $tmpfile;
        } else {
            $fileName = $this->fileNameFromUrl($ns, $url);
            return $this->dir . $fileName;
        }
    }

    /**
     * Removes all xsd, dtd temp files
     */
    private function removeFiles()
    {
        foreach ($this->filesToDelete as $fileToDel) {
            if (is_file($fileToDel)) {
                unlink($fileToDel);
            }
        }
    }

    /**
     * Creates the xsd schemas directory or checks it.
     * @param string $orderFromWeb the path to xsd schemas directory (from "web" directory relative).
     * @return string | null the absoulute path to xsd schemas directory or null.
     */
    private function createDir($orderFromWeb)
    {
        if ($orderFromWeb === null) {
            return null;
        }
        if (!is_dir($orderFromWeb)) {
            if (mkdir($orderFromWeb)) {
                return $orderFromWeb;
            } else {
                return null;
            }
        } else {
            return $orderFromWeb;
        }
    }

    private function isDirExists($filePath)
    {
        if (file_exists($filePath)) {
            if (is_file($filePath)) {
                return true;
            } else if (is_dir($filePath)) {
                return rmdir($filePath);
            } else {
                return true;
            }
        } else {
            mkdir($filePath, 0777, true);
            return $this->isDirExists($filePath);
        }
    }

    /**
     * Creates a new file name form namespace and url.
     * @param string $ns namespace
     * @param string $url url
     * @return string filename from a namespace and a url
     */
    private function fileNameFromUrl($ns, $url, $add = '')
    {
        $URL    = $this->normalizeUrl($ns, $url, $add);
        $urlArr = parse_url($URL);
        $path   = $urlArr['host'] . $urlArr['path'];
        // TODO querystring ?
        return $this->normalizePath($path, DIRECTORY_SEPARATOR);
    }

    private function normalizeUrl($ns, $url, $add = '')
    {
        $URL  = null;
        $temp = parse_url($url);
        if (isset($temp['scheme'])) {
            $URL = $url;
        } else { # use namespace as base url
            $URL = $ns . (strrpos($ns, '/') === strlen($ns) - 1 ? '' : '/') .
                (strpos($url, '/') === 0 ? substr($url, 1) : $url);
        }
        if ($add) {
            $URL .= (strrpos($URL, '/') === strlen($URL) - 1 ? '' : '/') .
                (strpos($add, '/') === 0 ? substr($add, 1) : $add);
        }
        $rawUrl      = parse_url($URL);
        $path        = $rawUrl['host'] . $rawUrl['path'];
        $schemelower = strtolower($rawUrl["scheme"]);
        $scheme      = "";
        if ($schemelower === 'http' || $schemelower === 'https' || $schemelower === 'ftp') {
            $scheme = $rawUrl["scheme"] . "://";
        } elseif ($schemelower === 'file') {
            $scheme = $rawUrl["scheme"] . ":///";
        } else {
            $scheme = $rawUrl["scheme"] . ":";
        }
        return $scheme . $this->normalizePath($path, '/') . (isset($rawUrl['query']) ? '?' . $rawUrl['query'] : '');
    }

    /**
     * Normalizes a file path: repaces all strings "/ORDERNAME/.." with "".
     * @param string $path
     * @return string a mormalized file path.
     */
    private function normalizePath($path, $separator)
    {
        $path = preg_replace("/[\/\\\][^\/\\\]+[\/\\\][\.]{2}/", "", $path);
        if (!strpos($path, "..")) {
            return preg_replace("/[\/\\\]/", $separator, $path);
        } else {
            $this->normalizePath($path);
        }
    }

    /**
     * Adds a schema "file:///" to file path.
     * @param string $filePath a file path
     * @return string a file path as url
     */
    private function addFileSchema($filePath)
    {
        $filePath_ = preg_replace("/[\/\\\]/", "/", $filePath);
        if (stripos($filePath_, "file:") !== 0) {
            return "file:///" . $filePath_;
        } else {
            return $filePath_;
        }
    }
}
