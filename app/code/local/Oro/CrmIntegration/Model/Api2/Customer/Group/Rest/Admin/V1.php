<?php
/**
 * @category  Oro
 * @package   Oro_CrmIntegration
 * @copyright Copyright 2016 Oro Inc. (http://www.orocrm.com)
 */
class Oro_CrmIntegration_Model_Api2_Customer_Group_Rest_Admin_V1 extends Oro_CrmIntegration_Model_Api2_Resource
{
    /**
     * Retrieve information about customer
     *
     * @throws Mage_Api2_Exception
     * @return array
     */
    protected function _retrieve()
    {
        $collection = $this->_getCollectionForRetrieve();
        $data = $collection->load()->toArray();
        $items = isset($data['items']) ? $data['items'] : $data;
        foreach ($items as &$item) {
            $item = Mage::helper('oro_crmintegration/api2')->m2RemapData($item, $this->getResourceType(), $this->getUserType());
        }

        return array(
            'items' => $this->getFilter()->collectionOut($items),
            'search_criteria' => $this->getSearchCriteria()->getFilters(),
            'total_count' => $collection->getSize()
        );
    }

    /**
     * @return Object
     */
    protected function _getCollectionForRetrieve()
    {
        $collection = Mage::getResourceModel('customer/group_collection');
        $this->_applyCollectionModifiersM2($collection);

        return $collection;
    }
}
