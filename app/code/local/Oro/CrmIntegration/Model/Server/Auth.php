<?php
/**
 * @category  Oro
 * @package   Oro_CrmIntegration
 * @copyright Copyright 2013 Oro Inc. (http://www.orocrm.com)
 */
class Oro_CrmIntegration_Model_Server_Auth
{
    const ORO_CONSUMER_CORE_FLAG = 'oro_rest_consumer';
    const ORO_CONSUMER_NAME = 'ORO CRM REST Consumer';

    /**
     * @param Mage_Api2_Model_Request $request
     * @return Mage_Api2_Model_Auth_User_Abstract
     */
    public function authenticate(Mage_Api2_Model_Request $request)
    {
        /** @var $helper Mage_Api2_Helper_Data */
        $helper    = Mage::helper('api2/data');
        $userTypes = $helper->getUserTypes();

        if (!$userTypes) {
            throw new Exception('No allowed user types found');
        }

        $userParamsObj = $this->_getUserParams($request);
        if (!isset($userTypes[$userParamsObj->type])) {
            throw new Mage_Api2_Exception(
                'Invalid user type or type is not allowed', Mage_Api2_Model_Server::HTTP_UNAUTHORIZED
            );
        }
        /** @var $userModel Mage_Api2_Model_Auth_User_Abstract */
        $userModel = Mage::getModel($userTypes[$userParamsObj->type]);

        if (!$userModel instanceof Mage_Api2_Model_Auth_User_Abstract) {
            throw new Exception('User model must to extend Mage_Api2_Model_Auth_User_Abstract');
        }
        // check user type consistency
        if ($userModel->getType() != $userParamsObj->type) {
            throw new Exception('User model type does not match appropriate type in config');
        }
        $userModel->setUserId($userParamsObj->id);

        return $userModel;
    }

    /**
     * @param Mage_Api2_Model_Request $request
     * @return string
     */
    public function getAdminToken(Mage_Api2_Model_Request $request)
    {
        $adminUser = $this->_adminLogin($request);
        $oauthToken = $this->_buildAdminOauthToken($adminUser);

        return (string)$oauthToken->getToken();
    }

    /**
     * @param Mage_Api2_Model_Request $request
     * @return Mage_Admin_Model_User
     */
    protected function _adminLogin(Mage_Api2_Model_Request $request)
    {
        $bodyParams = $request->getBodyParams();

        $username = !empty($bodyParams['username']) ? $bodyParams['username'] : '';
        $password = !empty($bodyParams['password']) ? $bodyParams['password'] : '';

        /** @var $user Mage_Admin_Model_User */
        $user = Mage::getModel('admin/user');
        $user->login($username, $password);
        if (!$user->getId()) {
            Mage::throwException(Mage::helper('adminhtml')->__('Invalid User Name or Password.'));
        }

        return $user;
    }

    /**
     * @param Mage_Admin_Model_User $adminUser
     * @return false|Mage_Core_Model_Abstract
     */
    protected function _buildAdminOauthToken(Mage_Admin_Model_User $adminUser)
    {
        $helper = Mage::helper('oauth');

        $oauthToken = Mage::getModel('oauth/token');
        $oauthConsumer = $this->_retriveOroConsumer();
        $oauthToken->setConsumerId($oauthConsumer->getId())
            ->setAdminId($adminUser->getId())
            ->setType(Mage_Oauth_Model_Token::TYPE_ACCESS)
            ->setToken($helper->generateToken())
            ->setSecret($helper->generateTokenSecret());
        $oauthToken->getResource()->save($oauthToken);

        return $oauthToken;
    }

    /**
     * Retreive Oro predefined REST API consumer
     *
     * @return Mage_Oauth_Model_Consumer
     */
    protected function _retriveOroConsumer() {
        $helper = Mage::helper('oauth');

        $flag = Mage::getModel('core/flag', array('flag_code' => self::ORO_CONSUMER_CORE_FLAG));
        $flag->loadSelf();
        /** @var $oauthConsumer */
        $oauthConsumer = Mage::getModel('oauth/consumer');
        $oauthConsumer->load((string)$flag->getFlagData(), 'key');

        if (!$oauthConsumer->getId()) {
            $oauthConsumer->setName(self::ORO_CONSUMER_NAME)
                ->setKey($helper->generateConsumerKey())
                ->setSecret($helper->generateConsumerSecret());
            $oauthConsumer->save();

            $flag->setFlagData($oauthConsumer->getKey());
            $flag->save();
        }

        return $oauthConsumer;
    }

    /**
     * @param Mage_Api2_Model_Request $request
     * @return object
     */
    protected function _getUserParams(Mage_Api2_Model_Request $request)
    {
        $userParamsObj = (object) array('type' => null, 'id' => null);

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
}
