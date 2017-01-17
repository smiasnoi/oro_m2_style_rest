<?php
/**
 * @category  Oro
 * @package   Oro_CrmIntegration
 * @copyright Copyright 2016 Oro Inc. (http://www.orocrm.com)
 */
class Oro_CrmIntegration_Model_Api2_Admintoken_Rest_Guest_V1 extends Oro_CrmIntegration_Model_Api2_Resource
{
    const ORO_CONSUMER_CORE_FLAG = 'oro_rest_consumer';
    const ORO_CONSUMER_NAME = 'ORO CRM REST Consumer';

    /**
     * Dispatch
     * To implement the functionality, you must create a method in the parent one.
     *
     * Action type is defined in api2.xml in the routes section and depends on entity (single object)
     * or collection (several objects).
     *
     * HTTP_MULTI_STATUS is used for several status codes in the response
     */
    public function dispatch()
    {
        switch ($this->getActionType() . $this->getOperation()) {
            /* Create */
            case self::ACTION_TYPE_ENTITY . self::OPERATION_CREATE:
                // Creation of objects is possible only when working with collection
                $data = $this->_getAdminToken();
                $this->_render($data);
                break;
            default:
                $this->_critical(self::RESOURCE_METHOD_NOT_IMPLEMENTED);
                break;
        }
    }

    /**
     * @param Mage_Api2_Model_Request $request
     * @return string
     */
    public function _getAdminToken()
    {
        $adminUser = $this->_adminLogin();
        $oauthToken = $this->_buildAdminOauthToken($adminUser);

        return (string)$oauthToken->getToken();
    }

    /**
     * @return Mage_Admin_Model_User
     */
    protected function _adminLogin()
    {
        $request = $this->getRequest();
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
}
