<?php
/**
 * @category  Oro
 * @package   Oro_CrmIntegration
 * @copyright Copyright 2016 Oro Inc. (http://www.orocrm.com)
 */
class Oro_CrmIntegration_Model_Api2_Directory_Rest_Admin_V1 extends Oro_CrmIntegration_Model_Api2_Resource
{
    const REGION_RESOURCE_NAME = 'oro_directory_region';
    protected $_regionFilter;
    /**
     * Retrieve information about customer
     *
     * @throws Mage_Api2_Exception
     * @return array
     */
    protected function _retrieve()
    {
        /** @var $country Mage_Directory_Model_Country */
        $countryId = $this->getRequest()->getParam('country_id');
        $country = Mage::getModel('directory/country')->load($countryId);
        if (!$country->getId()) {
            $this->_critical(self::RESOURCE_NOT_FOUND);
        }

        return $this->_getCountryData($country);
    }

    /**
     * @return array
     */
    protected function _retrieveCollection()
    {
        $collection = Mage::getResourceModel('directory/country_collection');
        $countriesData = array();
        foreach ($collection as $country) {
            $countriesData[] = $this->_getCountryData($country);
        }

        return $countriesData;
    }

    /**
     * @param Mage_Directory_Model_Country $country
     * @return array
     */
    protected function _getCountryData(Mage_Directory_Model_Country $country)
    {
        $helper = Mage::helper('oro_crmintegration/api2');

        $country->setData('full_name_english', $this->_getEnUsCountryName($country->getId()));
        $country->setFullNameLocale($country->getName());
        $countryData = $country->toArray();
        $countryData = $helper->m2RemapData($countryData, $this->getResourceType(), $this->getUserType());

        $regions = $country->getRegions();
        $regionsData = array();
        foreach ($regions as $region) {
            $regionData = $helper->m2RemapData($region->toArray(), self::REGION_RESOURCE_NAME, $this->getUserType());
            $regionsData[] = $this->_getRegionFilter()->out($regionData);
        }
        if ($regionsData) {
            $countryData['available_regions'] = $regionsData;
        }

        return $countryData;
    }

    /**
     * @return Mage_Api2_Model_Acl_Filter
     */
    protected function _getRegionFilter()
    {
        if (!$this->_regionFilter) {
            $self = clone $this;
            $self->setResourceType(self::REGION_RESOURCE_NAME);
            $filter = Mage::getModel('api2/acl_filter', $self);
            $this->_regionFilter = $filter;
        }

        return $this->_regionFilter;
    }

    /**
     * @param string $countryId
     * @return bool|string
     */
    protected function _getEnUsCountryName($countryId)
    {
        $result = Zend_Locale_Data::getContent('en_US', 'country', $countryId);
        if (empty($result) === true && '0' !== $result) {
            return false;
        }

        return $result;
    }
}
