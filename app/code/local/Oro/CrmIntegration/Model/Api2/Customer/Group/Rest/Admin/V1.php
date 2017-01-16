<?php
/**
 * @category  Oro
 * @package   Oro_CrmIntegration
 * @copyright Copyright 2016 Oro Inc. (http://www.orocrm.com)
 */
class Oro_CrmIntegration_Model_Api2_Customer_Group_Rest_Admin_V1 extends Mage_Api2_Model_Resource
{
    /**
     * Retrieve information about customer
     *
     * @throws Mage_Api2_Exception
     * @return array
     */
    protected function _retrieve()
    {
        $collection = $this->_getCollection();
        $this->_applyCollectionFilters($collection);
        $data = $this->_prepareResponseData($collection);

        return $data;
    }

    protected function _getCollection()
    {
        return Mage::getResourceModel('customer/groups_collection');
    }

    protected function _applyCollectionFilters($collection)
    {
        $criterias = new Varien_Object($this->getRequest()->getParam('searchCriteria'));
    }
}
