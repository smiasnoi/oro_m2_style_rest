<?php
/**
 * @category  Oro
 * @package   Oro_CrmIntegration
 * @copyright Copyright 2016 Oro Inc. (http://www.orocrm.com)
 */
class Oro_CrmIntegration_Model_Api2_Resource extends Mage_Api2_Model_Resource
{
    protected $_ignoredAttributeCodes = array();

    protected $searchCriteria;

    /**
     * Oro_CrmIntegration_Model_Api2_Resource constructor.
     */
    public function __construct()
    {
        $this->_searchCriteria = Mage::getSingleton('oro_crmintegration/api2_searchCriteria');
    }

    /**
     * @param Mage_Api2_Model_Request $request
     * @return $this
     */
    public function setRequest(Mage_Api2_Model_Request $request)
    {
        parent::setRequest($request);
        $this->_searchCriteria->setRequest($this->getRequest());

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSearchCriteria()
    {
        $this->_searchCriteria->setRequest($this->getRequest());
        return $this->_searchCriteria;
    }

    /**
     * Set navigation parameters and apply filters from URL params
     *
     * @param Varien_Data_Collection_Db $collection
     * @return Mage_Api2_Model_Resource
     */
    final public function applyCollectionModifiersM2(Varien_Data_Collection_Db $collection)
    {
        return $this->_applyCollectionModifiersM2($collection);
    }

    /**
     * Set navigation parameters and apply filters from URL params
     *
     * @param Varien_Data_Collection_Db $collection
     * @return Mage_Api2_Model_Resource
     */
    protected function _applyCollectionModifiersM2(Varien_Data_Collection_Db $collection)
    {
        $pageNumber = $this->getSearchCriteria()->getPageNumber();
        if ($pageNumber != abs($pageNumber)) {
            $this->_critical(self::RESOURCE_COLLECTION_PAGING_ERROR);
        }

        $pageSize = $this->getSearchCriteria()->getPageSize();
        if (null == $pageSize) {
            $pageSize = self::PAGE_SIZE_DEFAULT;
        } else {
            if ($pageSize != abs($pageSize) || $pageSize > self::PAGE_SIZE_MAX) {
                $this->_critical(self::RESOURCE_COLLECTION_PAGING_LIMIT_ERROR);
            }
        }

        $collection->setCurPage($pageNumber)->setPageSize($pageSize);

        $sortOrders = $this->getSearchCriteria()->getSortOrders();
        foreach ($sortOrders as $orderField => $orderDirection) {
            $operation = Mage_Api2_Model_Resource::OPERATION_ATTRIBUTE_READ;
            if (!is_string($orderField)
                || !array_key_exists($orderField, $this->getAvailableAttributes($this->getUserType(), $operation))
            ) {
                $this->_critical(self::RESOURCE_COLLECTION_ORDERING_ERROR);
            }
            $collection->setOrder($orderField, $orderDirection);
        }

        return $this->_applyFilterM2($collection);
    }

    /**
     * Validate filter data and apply it to collection if possible
     *
     * @param Varien_Data_Collection_Db $collection
     * @return Mage_Api2_Model_Resource
     */
    protected function _applyFilterM2(Varien_Data_Collection_Db $collection)
    {
        $filters = $this->getSearchCriteria()->getMergedFilters();

        if (!$filters) {
            return $this;
        }

        if (!is_array($filters)) {
            $this->_critical(self::RESOURCE_COLLECTION_FILTERING_ERROR);
        }
        if (method_exists($collection, 'addAttributeToFilter')) {
            $methodName = 'addAttributeToFilter';
        } elseif (method_exists($collection, 'addFieldToFilter')) {
            $methodName = 'addFieldToFilter';
        } else {
            return $this;
        }
        $allowedAttributes = $this->getFilter()->getAllowedAttributes(self::OPERATION_ATTRIBUTE_READ);

        $m2AttributesMap = Mage::helper('oro_crmintegration/api2')->getM2AttributesMap($this->getResourceType(), $this->getUserType());
        $m2AttributesMap = array_flip($m2AttributesMap);
        foreach ($filters as $filterEntry) {
            if (!is_array($filterEntry)
                || !array_key_exists('attribute', $filterEntry)
                || !in_array($filterEntry['attribute'], $allowedAttributes)
            ) {
                $this->_critical(self::RESOURCE_COLLECTION_FILTERING_ERROR);
            }
            $attributeCode = $filterEntry['attribute'];
            if (isset($m2AttributesMap[$attributeCode])) {
                $attributeCode = $m2AttributesMap[$attributeCode];
            }

            unset($filterEntry['attribute']);

            try {
                $collection->$methodName($attributeCode, $filterEntry);
            } catch(Exception $e) {
                Mage::logException($e);
                $this->_critical(self::RESOURCE_COLLECTION_FILTERING_ERROR);
            }
        }

        return $this;
    }

    /**
     * Get entity attributes that are not not present in known attributes list.
     *
     * @param Varien_Object $entity
     * @param array $data
     * @param array $exclude
     * @param array $include
     * @return array
     */
    protected function _getNotIncludedAttributes(
        Varien_Object $entity,
        array $data,
        array $exclude = array(),
        array $include = array()
    ) {
        $entityData = $entity->toArray();
        $knownAttributes = array_diff(array_keys($entityData), $exclude);
        $attributesToExpose = array_merge($knownAttributes, $include);

        $attributes = array();

        if (!empty($attributesToExpose)) {
            $attributes = array_intersect_key(
                array_merge($data, $entityData),
                array_combine($attributesToExpose, $attributesToExpose)
            );
        }

        return $this->_packAssoc($attributes);
    }

    /**
     * Pack associative array to format supported by API.
     *
     * @param array $data
     * @return array
     */
    protected function _packAssoc(array $data)
    {
        $result = array();
        foreach ($data as $key => $value) {
            $result[] = array(
                'key' => $key,
                'value' => $value
            );
        }

        return $result;
    }

    /**
     * @param string $type
     * @param array &$attributes
     * @return $this
     */
    protected function _cleanupAdditionalAttributes($type, &$attributes)
    {
        if (isset($this->_ignoredAttributeCodes[$type])) {
            $ignoredAttributes = $this->_ignoredAttributeCodes[$type];
            foreach ($attributes as $index => $attribute) {
                if (isset($attribute['key']) && in_array($attribute['key'], $ignoredAttributes)) {
                    unset($attributes[$index]);
                }
            }
        }

        return $this;
    }
}
