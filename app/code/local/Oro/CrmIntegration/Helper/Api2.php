<?php
/**
 * @category Oro
 * @package CrmIntegration
 * @copyright Copyright 2016 Oro Inc. (http://www.orocrm.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Oro_CrmIntegration_Helper_Api2 extends Mage_Core_Helper_Abstract
{
    protected $_m2AttributesMap;

    /**
     * @param $data
     * @param $resource
     * @param $userType
     * @return array
     */
    public function m2RemapData($data, $resource, $userType)
    {
        $mappedData = array();

        $map = $this->getM2AttributesMap($resource, $userType);
        foreach ($data as $key => $value) {
            if (isset($map[$key])) {
                $dest = $map[$key];
                $mappedData[$dest] = $value;
            } else {
                $mappedData[$key] = $value;
            }
        }

        return $mappedData;
    }

    /**
     * @param $resource
     * @param $userType
     * @return mixed
     */
    public function getM2AttributesMap($resource, $userType)
    {
        $key = $resource . $userType;
        if (!isset($this->_m2AttributesMap[$key])) {
            $config = Mage::getSingleton('api2/config');

            $node = $config->getNode('resources/' . $resource . '/m2_attributes_map/' . $userType);
            $this->_m2AttributesMap[$key] = $node ? $node->asCanonicalArray() : array();
        }

        return $this->_m2AttributesMap[$key];
    }

    /**
     * @param Mage_Api2_Model_Request $request
     * @return bool
     */
    public function m2RemappNeeded(Mage_Api2_Model_Request $request)
    {
        return (bool)$request->getHeader('M2Requested');
    }
}
