<?php
/**
 * @category  Oro
 * @package   Oro_CrmIntegration
 * @copyright Copyright 2016 Oro Inc. (http://www.orocrm.com)
 */
class Oro_CrmIntegration_Model_Api2_Customer_Rest_Admin_V1 extends Mage_Customer_Model_Api2_Customer_Rest_Admin_V1
{
    protected $_regionCodes;
    protected $_addressFilter;

    /**
     * Retrieve information about customer
     *
     * @throws Mage_Api2_Exception
     * @return array
     */
    protected function _retrieve()
    {
        $helper = Mage::helper('oro_crmintegration/api2');
        $customerId = $this->getRequest()->getParam('id');

        if ($customerId != 'search') {
            $data = parent::_retrieve();
            if ($helper->m2RemappNeeded($this->getRequest())) {
                $data['addresses'] = $this->_getCustomerAddressBook($customerId);
                $data = $helper->m2RemapData($data, $this->getResourceType(), $this->getUserType());
            }

            return $data;
        } else {
            $collection = $this->_getCollectionForRetrieveM2();
            $data = $collection->load()->toArray();
            $items = isset($data['items']) ? $data['items'] : $data;

            foreach ($items as &$item) {
                $customerId = isset($item['entity_id']) ? $item['entity_id'] : -1;
                $item['addresses'] = $this->_getCustomerAddressBook($customerId);
                $item = $this->getFilter()->out($item);
                $item = $helper->m2RemapData($item, $this->getResourceType(), $this->getUserType());
            }

            return array(
                'items' => $items,
                'search_criteria' => $collection->getFlag('m2_resource')->getSearchCriteria()->getFilters(),
                'total_count' => $collection->getSize()
            );
        }
    }

    /**
     * Retrieve collection instances filtered in M2 way
     *
     * @return Mage_Customer_Model_Resource_Customer_Collection
     */
    protected function _getCollectionForRetrieveM2()
    {
        /** @var $collection Mage_Customer_Model_Resource_Customer_Collection */
        $collection = Mage::getResourceModel('customer/customer_collection');
        $collection->addAttributeToSelect(array_keys(
            $this->getAvailableAttributes($this->getUserType(), Mage_Api2_Model_Resource::OPERATION_ATTRIBUTE_READ)
        ));

        /* @var Oro_CrmIntegration_Model_Api2_Resource */
        $m2Resource = Mage::getModel('oro_crmintegration/api2_resource');
        $m2Resource->setUserType($this->getUserType())
            ->setResourceType($this->getResourceType())
            ->setRequest($this->getRequest());
        $m2Resource->applyCollectionModifiersM2($collection);

        $collection->setFlag('m2_resource', $m2Resource);
        return $collection;
    }

    /**
     * @return false|Mage_Core_Model_Abstract
     */
    protected function _getAddressFilter()
    {
        if (!$this->_addressFilter) {
            $self = clone $this;
            $self->setResourceType('customer_address_alt');
            $filter = Mage::getModel('api2/acl_filter', $self);
            $this->_addressFilter = $filter;
        }

        return $this->_addressFilter;
    }

    /**
     * @return bool
     */
    protected function _m2RemappNeeded()
    {
        return true;
    }

    /**
     * @param int $customerId
     */
    protected function _getCustomerAddressBook($customerId)
    {

        $_addresses = Mage::getResourceModel('customer/address_collection');
        if (is_array($customerId)) {
            array_unshift($customerId, -1);
            $_addresses->addAttributeToFilter('parent_id', array('in' => $customerId));
        } else {
            $_addresses->addAttributeToFilter('parent_id', $customerId ?: -1);
        }
        $_addresses->addAttributeToSelect('*');

        $addresses = array();
        $index = 0;
        foreach ($_addresses as $_address) {
            $address = Mage::helper('oro_crmintegration/api2')->m2RemapData(
                $_address->getData(),
                'customer_address_alt',
                $this->getUserType()
            );
            $address['region'] = $this->_getRegionItems($_address);
            $address['street'] = explode("\n", $address['street']);

            $addresses[$index] = $this->_getAddressFilter()->out($address);
            $index++;
        }

        return $addresses;
    }

    /**
     * @param $address
     * @return array
     */
    protected function _getRegionItems($address)
    {
        return array(
            'region_id' => $address->getData('region_id'),
            'region' => $address->getData('region'),
            'region_code' => $this->_getRegionCodeById($address->getData('region_id'))
        );
    }

    /**
     * @param $regionId
     * @return mixed
     */
    protected function _getRegionCodeById($regionId)
    {
        if (!$this->_regionCodes) {
            $this->_regionCodes = array();
        }

        if (!isset($this->_regionCodes[$regionId])) {
            $region = Mage::getModel('directory/region')->load($regionId);
            $this->_regionCodes[$regionId] = $region->getCode();
        }

        return $this->_regionCodes[$regionId];
    }
}
