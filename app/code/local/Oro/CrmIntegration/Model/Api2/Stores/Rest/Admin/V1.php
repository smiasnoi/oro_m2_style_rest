<?php
/**
 * @category  Oro
 * @package   Oro_CrmIntegration
 * @copyright Copyright 2016 Oro Inc. (http://www.orocrm.com)
 */
class Oro_CrmIntegration_Model_Api2_Stores_Rest_Admin_V1 extends Oro_CrmIntegration_Model_Api2_Resource
{
    /**
     * Retrieve information about stores
     *
     * @throws Mage_Api2_Exception
     * @return array
     */
    protected function _retrieveCollection()
    {
        $helper = Mage::helper('oro_crmintegration/api2');

        /** @var Mage_Core_Model_Store[] $stores */
        $stores = Mage::app()->getStores(true);

        // Make result array
        $items = array();
        foreach ($stores as $store) {
            $items[] = $helper->m2RemapData($store->toArray(), $this->getResourceType(), $this->getUserType());
        }

        return $items;
    }
}
