<?php
/**
 * @category  Oro
 * @package   Oro_CrmIntegration
 * @copyright Copyright 2016 Oro Inc. (http://www.orocrm.com)
 */
class Oro_CrmIntegration_Model_Api2_Websites_Rest_Admin_V1 extends Oro_CrmIntegration_Model_Api2_Resource
{
    /**
     * Retrieve information about websites
     *
     * @throws Mage_Api2_Exception
     * @return array
     */
    protected function _retrieveCollection()
    {
        /** @var Mage_Core_Model_Website[] $websites */
        $websites = Mage::app()->getWebsites(true);

        // Make result array
        $items = array();
        foreach ($websites as $website) {
            $items[] = $website->toArray();
        }

        return $items;
    }
}
