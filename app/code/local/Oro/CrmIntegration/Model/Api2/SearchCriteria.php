<?php
/**
 * @category  Oro
 * @package   Oro_CrmIntegration
 * @copyright Copyright 2016 Oro Inc. (http://www.orocrm.com)
 */
class Oro_CrmIntegration_Model_Api2_SearchCriteria
{
    const SEARCH_CRITERIA_VAR = 'searchCriteria';

    const PAGE_SIZE_KEY = 'page_size';
    const CURRENT_PAGE_KEY = 'current_page';

    const FILTER_GROUPS_KEY = 'filter_groups';
    const FILTERS_KEY = 'filters';
    const FIELD_KEY = 'field';
    const VALUE_KEY = 'value';
    const CONDITION_TYPE_KEY = 'condition_type';
    const DEFAULT_CONDITION_TYPE = 'eq';

    const SORT_ORDERS_KEY = 'sort_orders';
    const DIRECTION_KEY = 'direction';
    const DEFAULT_SORT_DIRECTION = 'DESC';

    protected $_request;
    protected $_filters;
    protected $_mergedFilters;

    /**
     * @return Mage_Api2_Model_Request
     */
    public function getRequest()
    {
        if (!$this->_request) {
            $this->_request = Mage::getSingleton('api2/request');
        }

        return $this->_request;
    }

    /**
     * @return Mage_Core_Model_Abstract
     */
    public function setRequest(Zend_Controller_Request_Http $request)
    {
        $this->_request = $request;
        return $this;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        if (!$this->_filters) {
            $strFilter = new Zend_Filter_Word_CamelCaseToUnderscore();
            $request = $this->getRequest();
            $_searchCriteria = array_merge(
                (array)$request->getParam(self::SEARCH_CRITERIA_VAR),
                (array)$request->getParam(strtolower($strFilter->filter(self::SEARCH_CRITERIA_VAR)))
            );

            $searchCriteria = array();
            $this->_changeSearchCirteriaKeys($_searchCriteria, $searchCriteria, $strFilter);

            $this->_filters = (array)$searchCriteria;
            $this->_cleanupFiltersData();
        }

        return $this->_filters;
    }

    /**
     * @param $in
     * @param $out
     * @param $request
     */
    protected function _changeSearchCirteriaKeys($in, &$out, $strFilter)
    {
        foreach ($in as $key => $value) {
            $newKey = !is_numeric($key) ? strtolower($strFilter->filter($key)) : (int)$key;
            $out[$newKey] = $value;
            if (is_array($value)) {
                $this->_changeSearchCirteriaKeys($value, $out[$newKey], $strFilter);
            }
        }
    }

    /**
     * @return $this
     */
    protected function _cleanupFiltersData()
    {
        $_filters = $this->_filters;
        if (!$this->_filters) {
            return $this;
        }

        // cleanup fields/attributes filters
        if (isset($_filters[self::FILTER_GROUPS_KEY]) && (is_array($_filters[self::FILTER_GROUPS_KEY]))) {
            $filterGroups = &$_filters[self::FILTER_GROUPS_KEY];
            foreach ($filterGroups as $groupIndex => $group) {
                if (!is_array($group) && !isset($group[self::FILTERS_KEY])) {
                    unset($filterGroups[$groupIndex]);
                    continue;
                }

                $filters = &$filterGroups[$groupIndex][self::FILTERS_KEY];
                foreach ($filters as $filterIndex => $filter) {
                    if (empty($filter[self::FIELD_KEY]) || empty($filter[self::VALUE_KEY])) {
                        unset($filters[$filterIndex]);
                        continue;
                    }

                    $filters[$filterIndex][self::CONDITION_TYPE_KEY] =
                        !empty($filter[self::CONDITION_TYPE_KEY]) ? $filter[self::CONDITION_TYPE_KEY] : self::DEFAULT_CONDITION_TYPE;
                }
            }
        } else {
            $_filters[self::FILTER_GROUPS_KEY] = array();
        }

        // cleanup fields/attributes order
        if (isset($_filters[self::SORT_ORDERS_KEY]) && (is_array($_filters[self::SORT_ORDERS_KEY]))) {
            $sortOrders = &$_filters[self::SORT_ORDERS_KEY];
            foreach ($sortOrders as $sortIndex => $_sort) {
                if (empty($_sort[self::FIELD_KEY])) {
                    unset($sortOrders[$sortIndex]);
                    continue;
                }

                $sort = &$sortOrders[$sortIndex];
                if (empty($sort[DIRECTION_KEY])) {
                    $sort[DIRECTION_KEY] = self::DEFAULT_SORT_DIRECTION;
                }
            }
        } else {
            $_filters[self::SORT_ORDERS_KEY] = array();
        }

        $this->_filters = $_filters;
        return $this;
    }

    /**
     * @return int
     */
    public function getPageNumber()
    {
        $filters = $this->getFilters();
        return isset($filters[self::CURRENT_PAGE_KEY]) ? $filters[self::CURRENT_PAGE_KEY] : 1;
    }

    /**
     * @return int
     */
    public function getPageSize()
    {
        $filters = $this->getFilters();
        return isset($filters[self::PAGE_SIZE_KEY]) ? $filters[self::PAGE_SIZE_KEY] : null;
    }

    /**
     * @return mixed|null
     */
    public function getSortOrders()
    {
        $filters = $this->getFilters();
        return isset($filters[self::SORT_ORDERS_KEY]) ? $filters[self::SORT_ORDERS_KEY] : array();
    }

    /**
     * Flatterned and processed filter group
     * @return array()
     */
    public function getMergedFilters()
    {
        if (!$this->_mergedFilters) {
            $mergedFilters = array();
            $filters = $this->getFilters();
            foreach ($filters[self::FILTER_GROUPS_KEY] as $groups) {
                foreach ($groups as $group) {
                    foreach ($group as $filter) {
                        $mergedFilters[] = array(
                            'attribute' => $filter[self::FIELD_KEY],
                            $filter[self::CONDITION_TYPE_KEY] => $filter[self::VALUE_KEY]
                        );
                    }
                }
            }

            $this->_mergedFilters = $mergedFilters;
        }

        return $this->_mergedFilters;
    }
}
