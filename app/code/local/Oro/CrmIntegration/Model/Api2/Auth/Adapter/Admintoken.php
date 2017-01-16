<?php
/**
 * @category  Oro
 * @package   Oro_CrmIntegration
 * @copyright Copyright 2016 Oro Inc. (http://www.orocrm.com)
 */
class Oro_CrmIntegration_Model_Api2_Auth_Adapter_Admintoken extends Mage_Api2_Model_Auth_Adapter_Abstract
{
    /**
     * @param Mage_Api2_Model_Request $request
     * @return object
     */
    public function getUserParams(Mage_Api2_Model_Request $request)
    {
        $userParamsObj = (object) array('type' => 'guest', 'id' => null);

        $authHeader = $request->getHeader('Authorization');
        $authHeader = array_filter(explode(' ', $authHeader), 'trim');
        if (isset($authHeader[0]) && strtolower($authHeader[0]) == 'bearer'
            && !empty($authHeader[1])) {

            $token = $authHeader[1];
        } else {
            return $userParamsObj;
        }

        $oauthToken = Mage::getModel('oauth/token');
        $oauthToken->load($token, 'token');
        if ($oauthToken->getAdminId()) {
            $userParamsObj->id = $oauthToken->getAdminId();
            $userParamsObj->type = Mage_Oauth_Model_Token::USER_TYPE_ADMIN;
        }

        return $userParamsObj;
    }

    /**
     * Check if request contains authentication info for adapter
     *
     * @param Mage_Api2_Model_Request $request
     * @return boolean
     */
    public function isApplicableToRequest(Mage_Api2_Model_Request $request)
    {
        return true;
    }
}
