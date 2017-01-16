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
        $data = parent::_retrieve();
        if ($helper->m2RemappNeeded($this->getRequest())) {
            $customerId = $this->getRequest()->getParam('id');
            $data['addresses'] = $this->_getCustomerAddressBook($customerId);
            $data = $helper->m2RemapData($data, $this->getResourceType(), $this->getUserType());
        }

        return $data;
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
        $_addresses->addAttributeToFilter('parent_id', $customerId ?: -1)
            ->addAttributeToSelect('*');

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
