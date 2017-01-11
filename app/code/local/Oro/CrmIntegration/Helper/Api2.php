<?php
/**
 * @category Oro
 * @package CrmIntegration
 * @copyright Copyright 2016 Oro Inc. (http://www.orocrm.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Oro_CrmIntegration_Helper_Api2 extends Mage_Core_Helper_Abstract
{
    /**
     * @param $data
     * @param $resource
     * @param $userType
     * @return array
     */
    public function m2RemapData($data, $resource, $userType)
    {
        $config = Mage::getSingleton('api2/config');

        $mappedData = array();
        $node = $config->getNode('resources/' . $resource . '/m2_attributes_map/' . $userType);
        $map = $node ? $node->asCanonicalArray() : array();
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
     * @param Mage_Api2_Model_Request $request
     * @return bool
     */
    public function m2RemappNeeded(Mage_Api2_Model_Request $request)
    {
        return (bool)$request->getHeader('M2Requested');
    }
}
