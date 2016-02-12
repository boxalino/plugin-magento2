<?php

namespace Boxalino\Frontend\Block;

class Facets extends \Magento\Framework\View\Element\Template
{
    /** @var array */
    private $_allFilters = array();

    /** @var array */
    public $maxLevel = array();

    protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Boxalino\Frontend\Helper\P13n\Adapter $p13nHelper,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        if(!$p13nHelper->areThereSubPhrases()){
            $this->_allFilters = $p13nHelper->getFacetsData();
//            echo "<pre>",
//            print_r($this->_allFilters);
//            exit;
        }
    }

    /**
     * Gets the current URL without filters
     *
     * @return string
     */
    public function getResetUrl()
    {

        // get current url
        $url = $this->_urlBuilder->getCurrentUrl();

        // parse url
        $parsedUrl = parse_url($url);

        // build url
        $url = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'];

        // get query parameters
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $q);

            // remove bx filters
            foreach ($q as $k => $v) {
                if (strpos($k, 'bx_') === 0) {
                    unset($q[$k]);
                }
            }

            // append query string
            if ($q) {
                $url .= '?' . http_build_query($q);
            }
        }

        // return url
        return $url;
    }

    protected function getAllFilters() {}

    public function getTopFilters()
    {
        $filters = array();
        $filterOptions =  $this->_scopeConfig->getValue('Boxalino_General/filter', $this->scopeStore);
        $topFilters = explode(',', $filterOptions['top_filters']);
        $titles = explode(',', $filterOptions['top_filters_title']);
        $i = 0;
        $allFilters = $this->_allFilters;

        foreach ($topFilters as $filter) {
            $filter = trim($filter);
            if (isset($allFilters[$filter])) {
                foreach ($allFilters[$filter] as $key => $values) {
                    $yes = (strtolower($values['stringValue']) == 'yes');
                    if ($values['stringValue'] == 1 || $yes) {
                        $filters[$filter] = $allFilters[$filter][$key];
                        $filters[$filter]['title'] = $titles[$i];
                        $filters[$filter]['url'] = $this->_getTopFilterUrl($filter, $yes ? $values['stringValue'] : '1', $allFilters[$filter][$key]['selected']);
                        $filters[$filter]['selected'] = $allFilters[$filter][$key]['selected'];
                    }
                }
            }
            $i++;
        }
        return $filters;
    }

    public function getLeftFilters()
    {
        $filters = array();
        $leftFilters = explode(',',  $this->_scopeConfig->getValue('Boxalino_General/filter/left_filters_normal', $this->scopeStore));
//        print_r($leftFilters);
        $leftFiltersTitles = explode(',',  $this->_scopeConfig->getValue('Boxalino_General/filter/left_filters_normal_title', $this->scopeStore));
//        print_r($leftFiltersTitles);
        $i = 0;
        $allFilters = $this->_allFilters;
        foreach ($leftFilters as $filterString) {
            $position = 0;
            $filter = explode(':', $filterString);
            $filters[$filter[0]] = array('title' => $leftFiltersTitles[$i], 'values' => array());
            if (isset($allFilters[$filter[0]])) {
                if ($filter[1] == 'hierarchical') {
                    $filters[$filter[0]]['values'] = $this->_returnTree($filter[0]);
                } else {
                    foreach ($allFilters[$filter[0]] as $key => $values) {
                        $filters[$filter[0]]['values'][] = $this->_returnImportantValues($values, $filter[1], $filter[0], $position);
                        $position++;
                    }
                }
            }
            if (count($filters[$filter[0]]['values']) == 0) {
                unset($filters[$filter[0]]);
            }
            $i++;
        }
        $this->setResultCount("0");
        return $filters;
    }

    public function removeFilterFromUrl($url, $filter, $vals)
    {
        $key = 'bx_' . $filter;
        if (array_key_exists($key, $_REQUEST) && is_array($_REQUEST[$key])) {
            foreach ($vals as $val) {
                $position = array_search($val, $_REQUEST[$key]);
                if ($position !== false) {
                    $url = $this->_removeFilterFromUrl($url, $filter, $val, $position);
                }
            }
        }
        return $url;
    }

    public function getMinMaxValues($values)
    {
        $first = $values[0];
        $last = end($values);
        return array('min' => round(floor($first['stringValue']['min']), -2), 'max' => round(ceil($last['stringValue']['max'])), 1);
    }

    public function getMaxLevel($filter)
    {
        if (isset($this->maxLevel[$filter])) {
            return $this->maxLevel[$filter];
        }

        return 0;
    }

    protected function _addFilterToUrl($url, $filter, $value, $position = 0)
    {
        $query = parse_url($url, PHP_URL_QUERY);
        parse_str($query, $params);
        $key = 'bx_' . $filter;
        if (!array_key_exists($key, $params)) $params[$key] = array();
        $params[$key][$position] = $value;
        if (empty($query)) {
            return $url . '?' . http_build_query($params);
        }
        return str_replace($query, http_build_query($params), $url);
    }

    protected function _getFilterUrl($name, $value, $selected, $ranged = false, $position = 0, $hierarchical = null)
    {
        $multioption =  $this->_scopeConfig->getValue('Boxalino_General/filter/left_filters_multioption', $this->scopeStore);
        $currentUrl = $this->_urlBuilder->getCurrentUrl();
        if (!$ranged) {
            if ($multioption == true && $hierarchical == null) {
                if ($selected === false) {
                    $url = $this->_addFilterToUrl($currentUrl, $name, $value, $position);
                } else {
                    $url = $this->_removeFilterFromUrl($currentUrl, $name, $value, $position);
                }
            } else {
                $position = 0;
                if ($selected === false) {
                    if (array_key_exists('bx_' . $name, $_REQUEST) && is_array($_REQUEST['bx_' . $name])) {
                        foreach ($_REQUEST['bx_' . $name] as $val) {
                            $currentUrl = $this->_removeFilterFromUrl($currentUrl, $name, $val, $position);
                        }
                    }
                    $url = $this->_addFilterToUrl($currentUrl, $name, $value, $position);
                } else {
                    $url = $this->_removeFilterFromUrl($currentUrl, $name, $value, $position);
                }
            }
        } else {
            if ($selected === false) {
                $url = $this->_addFilterToUrl($currentUrl, $name, $value['from'] . '-' . $value['to'], $position);
            } else {
                $url = $this->_removeFilterFromUrl($currentUrl, $name, $value['from'] . '-' . $value['to'], $position);
            }
        }
        return $url;
    }

    protected function _getTopFilterUrl($name, $value, $selected)
    {
        $filterOptions =  $this->_scopeConfig->getValue('Boxalino_General/filter', $this->scopeStore);
        $multioption = $filterOptions['top_filters_multioption'];
        $currentUrl = $this->_urlBuilder->getCurrentUrl();
        if ($multioption == true) {
            if ($selected === false) {
                $url = $this->_addFilterToUrl($currentUrl, $name, $value);
            } else {
                $url = $this->_removeFilterFromUrl($currentUrl, $name, $value);
            }
        } else {
            if ($selected === false) {
                $topFilters = explode(',', $filterOptions['top_filters']);
                foreach ($topFilters as $filter) {
                    $filter = trim($filter);
                    $currentUrl = $this->_removeFilterFromUrl($currentUrl, $filter, $value);
                }
                $url = $this->_addFilterToUrl($currentUrl, $name, $value);
            } else {
                $url = $this->_removeFilterFromUrl($currentUrl, $name, $value);
            }
        }

        return $url;
    }

    protected function _removeFilterFromUrl($url, $filter, $value, $position = 0)
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if (!empty($query)) {
            parse_str($query, $params);
            $key = 'bx_' . $filter;
            if (
                array_key_exists($key, $params) &&
                array_key_exists($position, $params[$key]) &&
                $params[$key][$position] == $value
            ) {
                unset($params[$key][$position]);
                if (count($params[$key]) == 0) {
                    unset($params[$key]);
                }
            }
            if (count($params)) {
                return str_replace($query, http_build_query($params), $url);
            }
            return str_replace('?' . $query, '', $url);
        }
        return $url;
    }

    protected function _returnImportantValues($values, $option, $filter, $position)
    {
        $data = array();
        if ($option == 'ranged') {
            $data['stringValue'] = array('min' => $values['rangeFromInclusive'], 'max' => $values['rangeToExclusive']);
            $data['url'] = $this->_getFilterUrl($filter, array('from' => $values['rangeFromInclusive'], 'to' => $values['rangeToExclusive']), $values['selected'], true, $position);
        } else {
            $data['url'] = $this->_getFilterUrl($filter, $values['stringValue'], $values['selected'], false, $position);
            $data['stringValue'] = $values['stringValue'];
        }
        $data['hitCount'] = $values['hitCount'];
        $data['selected'] = $values['selected'];
        return $data;
    }

    protected function _returnHierarchy($filter)
    {
        $whatToDisplay = array('level' => 2, 'parentId' => '');
        $parents = array();
        $values = $this->_allFilters[$filter];
        $isCategories = ($filter == 'categories');
        $currentId = null;
        if ($isCategories && array_key_exists('bx_category_id', $_REQUEST)) {
            $currentId = current($_REQUEST['bx_category_id']);
        }

        $amount = count($values);
        for ($i = 0; $i < $amount; $i++) {
            $parentLevel = count($values[$i]['hierarchy']);
            for ($j = $i + 1; $j < $amount; $j++) {
                if ($parentLevel < count($values[$j]['hierarchy'])) {
                    $level = count($values[$j]['hierarchy']);
                    $childId = $values[$j]['hierarchyId'];
                    $parents[$level][$childId] = array(
                        'stringValue' => end($values[$j]['hierarchy']),
                        'hitCount' => $values[$j]['hitCount'],
                        'parentId' => $values[$i]['hierarchyId'],
                        'url' => $this->_getFilterUrl(
                            $isCategories ? 'category_id' : $filter,
                            $isCategories ? $values[$j]['hierarchyId'] : $values[$j]['stringValue'],
                            $isCategories ? $values[$j]['hierarchyId'] == $currentId : $values[$j]['selected'], false, 0
                        ),
                        'selected' => $isCategories ? $values[$j]['hierarchyId'] == $currentId : $values[$j]['selected']
                    );
                    if ($parents[$level][$childId]['selected'] === true) {
                        $whatToDisplay = array('level' => $level + 1, 'parentId' => $values[$j]['hierarchyId']);
                    }
                    continue;
                }
                if (count($values[$i]['hierarchy']) == count($values[$j]['hierarchy'])) {
                    break;
                }
            }
        }
        return array('values' => $parents, 'display' => $whatToDisplay);
    }

    protected function _returnTree($filter)
    {
        $results = array();
        $parents = $this->_returnHierarchy($filter);
        $level = 0;
        if ($parents['display']['level'] == 2) {
            $results = $parents['values'][$parents['display']['level']];
            return $results;
        } else {
            $highestChild = array();
            $level = $parents['display']['level'];
            $parentId = 0;
            if (isset($parents['values'][$level])) {
                $highestLevelCount = count($parents['values'][$level]);
                foreach ($parents['values'][$level] as $value) {
                    $parentId = $parents['display']['parentId'];
                    if ($value['parentId'] == $parentId) {
                        if ($highestLevelCount == 1) {
                            $value['selected'] = true;
                        }
                        $value['level'] = $level;
                        $highestChild[] = $value;
                    }
                }
            } else {
                $level = $level - 1;
                foreach ($parents['values'][$level] as $value) {
                    if ($value['selected'] == true) {
                        $parentId = $value['parentId'];
                        $value['level'] = $level;
                        $highestChild[] = $value;
                    }
                }

                foreach ($parents['values'][$level] as $value) {
                    if ($parentId == $value['parentId'] && $value['selected'] == false) {
                        $value['level'] = $level;
                        $highestChild[] = $value;
                    }
                }
            }

            for ($i = $level - 1; $i >= 2; $i--) {
                $parents['values'][$i][$parentId]['selected'] = true;
                $parents['values'][$i][$parentId]['level'] = $i;
                $results[] = $parents['values'][$i][$parentId];
                $parentId = $parents['values'][$i][$parentId]['parentId'];
            }
            $results = array_reverse($results);

        }
        $results = array_merge($results, $highestChild);
        $this->maxLevel[$filter] = $level;
        return $results;
    }

    public function lastSelected($filter){
        $selected = 0;
        $count = 0;
        foreach($filter as $value){
            if($value['selected']){
                $selected = $count;
            }
            $count++;
        }
        return $selected;
    }
}
