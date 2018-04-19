<?php
namespace ARP\SolrClient2;

require_once __DIR__ . '/SolrDocument.php';
require_once __DIR__ . '/CurlBrowser.php';

/**
 * SolrCore
 * @author A.R.Pour
 */
class SolrCore extends CurlBrowser
{
    protected $host      = null;
    protected $port      = null;
    protected $path      = null;
    protected $core      = null;
    protected $url       = null;
    protected $version   = null;
    protected $params    = array();
    protected $cache;
    protected $cacheSize = 10240;
    protected $content   = '';

    /**
     * Constructor.
     * @param array $options Options.
     */
    public function __construct($options)
    {
        $this->options($options);
    }

    /**
     * @param $options
     * @return $this
     */
    public function options($options)
    {
        if (is_string($options)) {
            $options = parse_url($options);
            $path = array_filter(explode('/', $options['path']));
            if (count($path) === 2) {
                $options['core'] = array_pop($path);
                $options['path'] = array_pop($path);
            }
        }

        if (isset($options['url']) && !empty($options['url'])) {
            $this->url = $options['url'];
        } else {
            $this->host    = isset($options['host']) ? $options['host'] : 'localhost';
            $this->port    = isset($options['port']) ? $options['port'] : 8080;
            $this->path    = isset($options['path']) ? $options['path'] : 'solr';
            $this->core    = isset($options['core']) ? $options['core'] : '';
        }

        $this->version = isset($options['version']) ? (int)$options['version'] : 4;

        $this->params = array(
            'fl'        => '*',
            'wt'        => 'json',
            'json.nl'   => 'map',
            'start'     => 0,
            'rows'      => 20,
            'q'         => '*:*'
        );

        if (isset($options['params'])) {
            $this->params = $this->mergeRecursive($this->params, $options['params']);
        }

        return $this;
    }

    /**
     * @param $url
     * @return $this
     */
    public function url($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @param $host
     * @return $this
     */
    public function host($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @param $port
     * @return $this
     */
    public function port($port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @param $path
     * @return $this
     */
    public function path($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @param $core
     * @return $this
     */
    public function core($core)
    {
        $this->core = $core;
        return $this;
    }

    /**
     * @deprecated
     * @param  string $core
     * @return SolrCore
     */
    public function fromCore($core)
    {
        trigger_error("Method 'fromCore' is deprecated use 'core' instead.", E_USER_DEPRECATED);
        return $this->core($core);
    }

    /**
     * @param $version
     * @return $this
     */
    public function version($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function params(array $params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * @param $size
     * @return $this
     */
    public function cacheSize($size)
    {
        $this->cacheSize = (int)$size;
        return $this;
    }

    /**
     * @return SolrDocument
     */
    public function newDocument()
    {
        return new SolrDocument();
    }

    /**
     * @param SolrDocument $document
     * @return $this
     */
    public function addDocument(SolrDocument $document)
    {
        $this->jsonUpdate($document->toJson());
        return $this;
    }

    /**
     * @param $documents
     * @return $this
     */
    public function addDocuments($documents)
    {
        $json = '';

        foreach ($documents as $document) {
            $json .= substr($document->toJson(), 1, -1) . ',';
        }

        $this->jsonUpdate('{' . substr($json, 0, -1) . '}');
        return $this;
    }

    /**
     * @param SolrDocument $document
     * @return $this|null|\stdClass
     */
    public function appendDocument(SolrDocument $document)
    {
        $this->cache .= substr($document->toJson(), 1, -1) . ',';
        if (strlen($this->cache) >= $this->cacheSize) {
            return $this->commitCachedDocuments();
        }
        return $this;
    }

    /**
     * @param $query
     * @return $this
     */
    public function deleteByQuery($query)
    {
        $this->jsonUpdate('{"delete": { "query":"' . $query . '" }}');
        return $this;
    }

    /**
     * @return $this
     */
    public function deleteAll()
    {
        $this->deleteByQuery('*:*');
        return $this;
    }

    /**
     * @return $this
     */
    public function commit()
    {
        $this->commitCachedDocuments();
        $this->jsonUpdate('{"commit": {}}');
        return $this;
    }

    /**
     * @param bool $waitSearcher
     * @return $this
     */
    public function optimize($waitSearcher = false)
    {
        $this->jsonUpdate('{"optimize": {"waitSearcher":' . $waitSearcher ? 'true' : 'false' . '}}', false);
        return $this;
    }

    /**
     * @return string
     */
    public function queryInfo()
    {
        return urldecode($this->content) .
            '<pre>' . print_r($this->params, true) . '</pre>';
    }

    /**
     * @param $params
     * @return \stdClass
     */
    protected function solrSelect($params)
    {
        $this->content = http_build_query($params);
        $this->content = preg_replace('/%5B([\d]{1,2})%5D=/', '=', $this->content);

        $response = $this->httpPost(
            $this->generateURL('select'),
            array('Content-type: application/x-www-form-urlencoded'),
            $this->content
        );

        if ($response->status >= 400 && $response->status < 600) {
            throw new \RuntimeException("\nStatus: $response->status\nContent: $response->content");
        }

        return $response;
    }

    /**
     * @param $arr1
     * @param $arr2
     * @return mixed
     */
    protected function mergeRecursive($arr1, $arr2)
    {
        foreach (array_keys($arr2) as $key) {
            if (isset($arr1[$key]) && is_array($arr1[$key]) && is_array($arr2[$key])) {
                $arr1[$key] = $this->mergeRecursive($arr1[$key], $arr2[$key]);
            } else {
                $arr1[$key] = $arr2[$key];
            }
        }
        return $arr1;
    }

    /**
     * @param $string
     * @param bool $cached
     */
    protected function appendToFilter($string, $cached = true)
    {
        if (!$cached) {
            $string = '{!cache=false}' . $string;
        }

        if (!isset($this->params['fq'])) {
            $this->params['fq'] = array($string);
        } else {
            $this->params['fq'][] = $string;
        }
    }

    /**
     * @param string $path
     * @return string
     */
    private function generateURL($path = '')
    {
        if ($this->url !== null) {
            return $this->url;
        }

        return 'http://'
            . $this->host
            . ($this->port === null ?: ':' . $this->port)
            . ($this->path === null ?: '/' . $this->path)
            . ($this->core === null ?: '/' . $this->core)
            . ($path == '' ?: '/' . $path);
    }

    /**
     * @param $content
     * @param bool $checkStatus
     * @return \stdClass
     */
    public function jsonUpdate($content, $checkStatus = true)
    {
        if ($this->version == 4) {
            $url = $this->generateURL('update');
        } else {
            $url = $this->generateURL('update/json');
        }

        $response = $this->httpPost(
            $url,
            array('Content-type: application/json'),
            $content
        );

        if ($checkStatus && $response->status >= 400 && $response->status < 600) {
            throw new \RuntimeException("\nStatus: $response->status\nContent: $response->content");
        }

        return $response;
    }

    /**
     * @return null|\stdClass
     */
    private function commitCachedDocuments()
    {
        if (strlen($this->cache) > 1) {
            $response = $this->jsonUpdate('{' . substr($this->cache, 0, -1) . '}');
            $this->cache = '';
            return $response;
        }
        return null;
    }
}
