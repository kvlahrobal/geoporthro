<?php
namespace ARP\SolrClient2;

/**
 * CurlBrowser
 * @author A.R.Pour
 */
class CurlBrowser
{
    private $timeout = 13;
    private $proxy_host = null;
    private $proxy_port = null;
    private $proxy_exclude = null;
    private $logger = null;

    /**
     * If a logger is set, request urls and bodies will be logged on
     * debug level.
     *
     * @param logger a logger instance, e.g. monolog as used by
     * symfony2 (any PSR-3 logger should do)
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param $url
     * @return \stdClass
     */
    public function httpGet($url)
    {
        return $this->doRequest('GET', $url, array(), null);
    }

    /**
     * @param $url
     * @param array $header
     * @param $data
     * @return \stdClass
     */
    public function httpPost($url, array $header, $data)
    {
        return $this->doRequest('POST', $url, $header, $data);
    }

    /**
     * @param $timeout
     */
    public function timeout($timeout)
    {
        $this->timeout = (int)$timeout;
    }

    /**
     * @param $host
     * @param $port
     * @return bool
     */
    public function proxy($host, $port)
    {
        if (trim($host) === "" || trim($port) === '') {
            return false;
        }

        $this->proxy_host = trim($host);
        $this->proxy_port = trim($port);
        return true;
    }

    /**
     * @param $host
     * @return bool
     */
    public function excludeHost($host)
    {
        if (trim($host) === "") {
            return false;
        }

        $this->proxy_exclude[] = trim($host);
        return true;
    }

    /**
     * @param $method
     * @param $url
     * @param array $header
     * @param $data
     * @return \stdClass
     */
    private function doRequest($method, $url, array $header, $data)
    {
        $curlInit = curl_init();

        $parsed_url = parse_url($url);

        if ($this->logger) {
            $this->logger->debug('Requesting via ' . $method . ' against ' . $url);
            if (is_array($data)) {
                $this->logger->debug('KVP values as json: ' . json_encode($data));
            } else {
                $this->logger->debug('Body: ' . $data);
            }
            $this->logger->debug('Headers: ' . $header);
        }

        if (isset($parsed_url['scheme'])
            && $parsed_url['scheme'] !== 'http'
            && $parsed_url['scheme'] !== 'https') {
            return false;
        }

        if ($method === 'GET') {
            curl_setopt($curlInit, CURLOPT_POST, 0);
        } elseif ($method === 'POST') {
            curl_setopt($curlInit, CURLOPT_POST, 1);
            curl_setopt($curlInit, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($curlInit, CURLOPT_URL, $url);
        curl_setopt($curlInit, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlInit, CURLINFO_HEADER_OUT, true);
        curl_setopt($curlInit, CURLOPT_TIMEOUT, $this->timeout);

        if (!empty($header)) {
            curl_setopt($curlInit, CURLOPT_HTTPHEADER, $header);
        }

        if (!empty($this->proxy_host) && !in_array($parsed_url["host"], $this->proxy_exclude)) {
            curl_setopt($curlInit, CURLOPT_PROXY, $this->proxy_host);
            curl_setopt($curlInit, CURLOPT_PROXYPORT, $this->proxy_port);
        }

        $response = new \stdClass();
        $response->content = curl_exec($curlInit);
        $response->header = curl_getInfo($curlInit, CURLINFO_HEADER_OUT);
        $response->status = (int)curl_getInfo($curlInit, CURLINFO_HTTP_CODE);
        $response->contentType = curl_getInfo($curlInit, CURLINFO_CONTENT_TYPE);
        $response->error = curl_error($curlInit);

        curl_close($curlInit);
        return $response;
    }
}
