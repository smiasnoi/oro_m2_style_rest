<?php
/**
 * @category  Oro
 * @package   Oro_CrmIntegration
 * @copyright Copyright 2016 Oro Inc. (http://www.orocrm.com)
 */
class Oro_CrmIntegration_Model_Api2_Resource extends Mage_Api2_Model_Resource
{
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
    protected function _applyCollectionModifiersV2(Varien_Data_Collection_Db $collection)
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

        /*$sortOrders = $this->getSearchCriteria()->getSortOrders();
        foreach ($sortOrders as $orderField => $orderDirection) {
            $operation = Mage_Api2_Model_Resource::OPERATION_ATTRIBUTE_READ;
            if (!is_string($orderField)
                || !array_key_exists($orderField, $this->getAvailableAttributes($this->getUserType(), $operation))
            ) {
                $this->_critical(self::RESOURCE_COLLECTION_ORDERING_ERROR);
            }
            $collection->setOrder($orderField, $orderDirection);
        }

        $this->_applyFilter($collection);*/
    }
}