<?php
/**
 * @category  Oro
 * @package   Oro_CrmIntegration
 * @copyright Copyright 2016 Oro Inc. (http://www.orocrm.com)
 */
class Oro_CrmIntegration_Model_Api2_Ping_Rest_Admin_V1 extends Oro_CrmIntegration_Model_Api2_Resource
{
    /**
     * Retrieve information about customer
     *
     * @throws Mage_Api2_Exception
     * @return array
     */
    protected function _retrieveCollection()
    {
        $data = $this->_getCollectionForRetrieve()->load()->toArray();
        return isset($data['items']) ? $data['items'] : $data;
    }

    /**
     * @return Object
     */
    protected function _getCollectionForRetrieve()
    {
        /* @var Mage_Sales_Model_Resource_Quote_Collection */
        $quoteCollection = Mage::getResourceModel('sales/quote_collection');
        return $quoteCollection;
    }
}
