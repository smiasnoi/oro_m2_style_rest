<?php
/**
 * @category  Oro
 * @package   Oro_CrmIntegration
 * @copyright Copyright 2016 Oro Inc. (http://www.orocrm.com)
 */
class Oro_CrmIntegration_Model_Api2_Ping_Rest_Admin_V1 extends Mage_Api2_Model_Resource
{
    /**
     * Retrieve information about customer
     *
     * @throws Mage_Api2_Exception
     * @return array
     */
    protected function _retrieve()
    {
        $customerScope = (int)Mage::getSingleton('customer/config_share')->isWebsiteScope();

        return array(
            'version'        => (string)Mage::getConfig()->getNode('modules/Oro_Api/version'),
            'mage_version'   => Mage::getVersion(),
            'admin_url'      => Mage::getUrl('adminhtml'),
            'customer_scope' => $customerScope,
        );
    }
}
