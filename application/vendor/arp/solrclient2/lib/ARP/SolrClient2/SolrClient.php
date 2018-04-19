<?php
namespace ARP\SolrClient2;

require_once __DIR__ . '/Paging.php';
require_once __DIR__ . '/SolrQuery.php';

/**
 * SolrClient
 * @author A.R.Pour
 */
class SolrClient extends SolrQuery
{
    protected $pagingLength = 10;
    protected $wordWildcard = true;
    protected $numericWildcard = false;
    protected $leftWildcard = false;
    protected $wildcardMinStrlen = 3;
    protected $searchTerms = array();
    protected $autocompleteField = '';
    protected $autocompleteLimit = 10;
    protected $autocompleteSort = 'count';
    protected $fuzzy = false;

    /**
     * @param null $options
     */
    public function __construct($options = null)
    {
        parent::__construct($options);
    }

    /**
     * @param $fuzzy
     * @param string $percent
     * @return $this
     */
    public function fuzzy($fuzzy, $percent = '')
    {
        if ($fuzzy && $this->version >= 4) {
            $this->fuzzy = '~' . $percent;
        }

        return $this;
    }

    /**
     * @param $length
     * @return $this
     */
    public function pagingLength($length)
    {
        $this->pagingLength = (int)$length;
        return $this;
    }

    /**
     * @param $wordWildcard
     * @return $this
     */
    public function wordWildcard($wordWildcard)
    {
        $this->wordWildcard = (boolean)$wordWildcard;
        return $this;
    }

    /**
     * @param $numericWildcard
     * @return $this
     */
    public function numericWildcard($numericWildcard)
    {
        $this->numericWildcard = (boolean)$numericWildcard;
        return $this;
    }

    /**
     * @param $leftWildcard
     * @return $this
     */
    public function leftWildcard($leftWildcard)
    {
        $this->leftWildcard = (boolean)$leftWildcard;
        return $this;
    }

    /**
     * @param $wildcardMinStrlen
     * @return $this
     */
    public function wildcardMinStrlen($wildcardMinStrlen)
    {
        $this->wildcardMinStrlen = (int)$wildcardMinStrlen;
        return $this;
    }

    /**
     * @param $field
     * @param int $limit
     * @param string $sort
     * @return $this
     */
    public function autocomplete($field, $limit = 10, $sort = 'count')
    {
        $this->autocompleteField = trim($field);
        $this->autocompleteLimit = (int)$limit;
        $this->autocompleteSort = $sort;
        return $this;
    }

    /**
     * @param string $string
     * @param null $q
     * @param null $page
     * @param null $hits
     * @param array $params
     * @return Object
     */
    public function find($string = '', $q = null, $page = null, $hits = null, $params = array())
    {
        $this->searchTerms = array_filter(explode(' ', $string));

        if ($this->autocompleteField !== '') {
            $this->params['facet'] = 'on';

            if (!isset($this->params['facet.field'])) {
                $this->params['facet.field'] = array($this->autocompleteField);
            } else {
                $this->params['facet.field'][] = $this->autocompleteField;
            }

            $this->params['f.' . $this->autocompleteField . '.facet.sort'] = $this->autocompleteSort;
            $this->params['f.' . $this->autocompleteField . '.facet.limit'] = $this->autocompleteLimit;
            $this->params['f.' . $this->autocompleteField . '.facet.prefix'] = end($this->searchTerms);
            $this->params['f.' . $this->autocompleteField . '.facet.mincount'] = 1;
        }

        if (!is_null($q)) {
            $response = $this->exec($q, $page, $hits, $params);
        } else {
            $response = $this->exec($this->buildQuery('standardQuery'), $page, $hits, $params);
        }

        // PREPAIR PAGING
        if (isset($response->count) && isset($response->offset)) {
            $paging = new Paging($response->count, $this->params['rows'], null, $response->offset);

            foreach ($paging->calculate() as $key => $val) {
                $response->$key = $val;
            }
        }

        return $response;
    }

    /**
     * @param $method
     * @param null $terms
     * @return null|string
     */
    private function buildQuery($method, $terms = null)
    {
        if (is_null($terms)) {
            $terms = $this->searchTerms;
        }

        if (count($terms) !== 0) {
            array_walk($terms, 'self::' . $method);
            return implode(' ', $terms);
        }

        return null;
    }

    /**
     * @param $term
     */
    private function standardQuery(&$term)
    {
        $rawTerm = trim($term);

        // NORMAL
        $term = $this->escape($rawTerm) . '^1';

        // WILDCARD
        if ((is_numeric($rawTerm) && $this->numericWildcard)
            || (!is_numeric($rawTerm) && $this->wordWildcard)
            && strlen($rawTerm) >= $this->wildcardMinStrlen) {

            $term .= ' OR '
                . ($this->leftWildcard ? '*' : '')
                . $this->escape($rawTerm)
                . '*^0.6';
        }

        // FUZZY
        if (!empty($this->fuzzy)
            && strlen($rawTerm) >= $this->wildcardMinStrlen
            && !is_numeric($rawTerm)) {

            $term .= ' OR '
                . $this->escape($rawTerm)
                . $this->fuzzy
                . '^0.3';
        }

        $term = '(' . $term . ')';
    }
}
