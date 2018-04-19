<?php
namespace ARP\SolrClient2;

require_once __DIR__ . '/SolrCore.php';

/**
 * SolrQuery
 * @author A.R.Pour
 */
class SolrQuery extends SolrCore
{
    protected $page = 1;

    /**
     * Constructor.
     * @param array $options Options.
     */
    public function __construct($options)
    {
        parent::__construct($options);
    }

    /**
     * Search function.
     * @param  string   $query  Search query.
     * @param  integer  $offset   Start offset.
     * @param  integer  $limit   Hits per page.
     * @param  array    $params Parameters
     * @return Object   Result  documents.
     */
    public function exec($query = null, $page = null, $hits = null, $params = array())
    {
        $this->params = $this->mergeRecursive($this->params, $params);

        if (!is_null($hits)) {
            $this->params['rows'] = (int)$hits;
        }
        if (!empty($query)) {
            $this->params['q'] = $query;
        }
        if (!is_null($page)) {
            $this->page($page);
        }

        // calculate offset
        if ($this->params['start'] === 0 && $this->page > 1) {
            $this->offset(($this->page * $this->params['rows']) - $this->params['rows']);
        }

        $response = $this->solrSelect($this->params);

        $content = json_decode($response->content);

        unset($response);

        /***********************************************************************
         * PREPAIR SOLR SEARCH RESULT
         ***********************************************************************/
        $result = new \stdClass();

        if (isset($content->response->numFound)) {
            $result->count = $content->response->numFound;
        }

        if (isset($content->response->start)) {
            $result->offset = $content->response->start;
        }

        if (isset($content->response->docs) && !empty($content->response->docs)) {
            $result->documents = $content->response->docs;
        }

        if (isset($content->facet_counts->facet_fields)) {
            foreach ($content->facet_counts->facet_fields as $key => $val) {
                if ($this->autocompleteField === $key) {
                    $result->autocomplete = (array)$val;
                } else {
                    $result->facets[$key] = (array)$val;
                }
            }
        }

        return $result;
    }

    /**
     * @param $debug
     * @return $this
     */
    public function debug($debug)
    {
        if ($debug) {
            $this->params['deubgQuery'] = 'true';
        } elseif (isset($this->params['deubgQuery'])) {
            unset($this->params['deubgQuery']);
        }

        return $this;
    }

    /**
     * @param $page
     * @return $this
     */
    public function page($page)
    {
        $this->page = (int)$page > 0 ? (int)$page : 1;
        return $this;
    }

    /**
     * @param $offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->params['start'] = (int)$offset;
        return $this;
    }

    /**
     * @param $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->params['rows'] = (int)$limit;
        return $this;
    }

    /**
     * @param $select
     * @return $this
     */
    public function select($select)
    {
        $this->params['fl'] = $select;
        return $this;
    }

    /**
     * @param $key
     * @param null $value
     * @param bool $cached
     * @param string $innerOperator
     * @return $this
     */
    public function where($key, $value = null, $cached = true, $innerOperator = 'OR')
    {
        //TODO: REMOVE DEFAULT OPERATOR
        $tmp = "";

        // many fields ...
        if (is_array($key) && is_null($value)) {
            foreach ($key as $k => $v) {
                // ... and many values
                if (is_string($k) && is_array($v)) {
                    foreach ($v as $val) {
                        $tmp .= ' ' . $innerOperator . ' ' . $k . ':"' . $this->escapePhrase($val) . '"';
                    }

                // ... one value
                } elseif (is_string($k)) {
                    $tmp .= ' ' . $innerOperator . ' ' . $k . ':"' . $this->escapePhrase((string)$v) . '"';
                }
            }

            if ($tmp !== "") {
                $this->appendToFilter(trim(substr($tmp, 4)), $cached);
            }

        // one field and many values
        } elseif (is_string($key) && is_array($value)) {
            foreach ($value as $val) {
                $tmp .= ' ' . $innerOperator . ' ' . $key . ':"' . $this->escapePhrase($val) . '"';
            }

            $this->appendToFilter(trim(substr($tmp, 4)), $cached);

        // one field an one value
        } elseif (is_string($key) && !is_null($value)) {
            $this->appendToFilter($key . ':"' . $this->escapePhrase((string)$value) . '"', $cached);

        // raw filterquery
        } elseif (is_string($key)) {
            $this->appendToFilter($key, $cached);
        }

        return $this;
    }

    /**
     * @param $sort
     * @param string $direction
     * @return $this
     */
    public function orderBy($sort, $direction = 'asc')
    {
        $this->params['sort'] = $sort . ' ' . $direction;
        return $this;
    }

    /**
     * @param $queryParser
     * @return $this
     */
    public function queryParser($queryParser)
    {
        $this->params['defType'] = $queryParser;
        return $this;
    }

    /**
     * @param $fields
     * @param int $mincount
     * @param string $sort
     * @return $this
     */
    public function facet($fields, $mincount = 1, $sort = 'index', $limit = null)
    {
        if (is_string($fields)) {
            $fields = array($fields);
        }

        $this->params['facet'] = 'on';
        $this->params['facet.field'] = $fields;
        $this->params['facet.mincount'] = $mincount;
        $this->params['facet.sort'] = $sort;

        if (!is_null($limit)) {
            $this->params['facet.limit'] = (int)$limit;
        }

        return $this;
    }

    /**
     * Escape searchstring.
     * @param  string $string Searchstring.
     * @return string         Escaped searchstring.
     */
    public function escape($string)
    {
        return preg_replace(
            '/(\+|-|&&|\|\||!|\(|\)|\{|}|\[|]|\^|"|~|\*|\?|:|\/|\\\)/',
            '\\\$1',
            $string
        );
    }

    /**
     * Escape search phrase.
     * @param  string $string Phrase string.
     * @return string         Escaped phrase.
     */
    public function escapePhrase($string)
    {
        return preg_replace(
            '/("|\\\)/',
            '\\\$1',
            $string
        );
    }
}
