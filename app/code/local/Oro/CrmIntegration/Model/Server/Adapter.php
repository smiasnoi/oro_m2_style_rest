<?php
/**
 * @category  Oro
 * @package   Oro_CrmIntegration
 * @copyright Copyright 2013 Oro Inc. (http://www.orocrm.com)
 */
class Oro_CrmIntegration_Model_Server_Adapter
    extends Mage_Api2_Model_Server
    implements Mage_Api_Model_Server_Adapter_Interface
{
    protected $_handler;
    protected $_controller;

    /**
     * Set handler class name for webservice
     *
     * @param string $handler
     * @return Mage_Api_Model_Server_Adapter_Interface
     */
    public function setHandler($handler)
    {
        $this->_handler = $handler;
        return $this;
    }

    /**
     * Retrive handler class name for webservice
     *
     * @return string
     */
    public function getHandler()
    {
        return $this->_handler;
    }

    /**
     * Set webservice api controller
     *
     * @param Mage_Api_Controller_Action $controller
     * @return Mage_Api_Model_Server_Adapter_Interface
     */
    public function setController(Mage_Api_Controller_Action $controller)
    {
        $this->_controller = $controller;
    }

    /**
     * Retrive webservice api controller
     *
     * @return Mage_Api_Controller_Action
     */
    public function getController()
    {
        return $this->_controller;
    }

    /**
     * Dispatch webservice fault
     *
     * @param int $code
     * @param string $message
     */
    public function fault($code, $message)
    {
        Mage::log($code . ' : ' . $message);
    }

    /**
     * Run webservice
     *
     * @param Mage_Api_Controller_Action $controller
     * @return Mage_Api_Model_Server_Adapter_Soap
     */
    public function run()
    {
        // can not use response object case
        try {
            /** @var $response Mage_Api2_Model_Response */
            $response = Mage::getSingleton('api2/response');
        } catch (Exception $e) {
            Mage::logException($e);

            if (!headers_sent()) {
                header('HTTP/1.1 ' . self::HTTP_INTERNAL_ERROR);
            }
            echo 'Service temporary unavailable';
            return;
        }
        // can not render errors case
        try {
            /** @var $request Mage_Api2_Model_Request */
            $request = Mage::getSingleton('api2/request');
            /** @var $renderer Mage_Api2_Model_Renderer_Interface */
            $renderer = Mage_Api2_Model_Renderer::factory($request->getAcceptTypes());
        } catch (Exception $e) {
            Mage::logException($e);

            $response->setHttpResponseCode(self::HTTP_INTERNAL_ERROR)
                ->setBody('Service temporary unavailable')
                ->sendResponse();
            return;
        }

        // default case
        try {
            $this->_route($request);
            if (!$request->getParam('admin_token_request')) {
                /** @var $apiUser Mage_Api2_Model_Auth_User_Abstract */
                $apiUser = $this->_authenticate($request);
                $this->_allow($request, $apiUser)
                    ->_dispatch($request, $response, $apiUser);
            } else {
                $this->_adminToken($request, $response);
            }

            if ($response->getHttpResponseCode() == self::HTTP_CREATED) {
                // TODO: Re-factor this after _renderException refactoring
                throw new Mage_Api2_Exception('Resource was partially created', self::HTTP_CREATED);
            }
            //NOTE: At this moment Renderer already could have some content rendered, so we should replace it
            if ($response->isException()) {
                throw new Mage_Api2_Exception('Unhandled simple errors.', self::HTTP_INTERNAL_ERROR);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_renderException($e, $renderer, $response);
        }

        $response->sendResponse();
    }

    /**
     * Set all routes of the given api type to Route object
     * Find route that match current URL, set parameters of the route to Request object
     *
     * @param Mage_Api2_Model_Request $request
     * @return Mage_Api2_Model_Server
     */
    protected function _route(Mage_Api2_Model_Request $request)
    {
        /** @var $router Oro_CrmIntegration_Model_Server_Router */
        $router = Mage::getModel('oro_crmintegration/server_router');

        $router->routeApiType($request, true)
            ->setRoutes($this->_getConfig()->getRoutes($request->getApiType()))
            ->route($request);

        return $this;
    }

    /**
     * Authenticate user
     *
     * @throws Exception
     * @param Mage_Api2_Model_Request $request
     * @return Mage_Api2_Model_Auth_User_Abstract
     */
    protected function _authenticate(Mage_Api2_Model_Request $request)
    {
        /** @var $authManager Oro_CrmIntegration_Model_Server_Auth */
        $authManager = Mage::getSingleton('oro_crmintegration/server_auth');

        $this->_setAuthUser($authManager->authenticate($request));
        return $this->_getAuthUser();
    }

    /**
     *
     * @param Mage_Api2_Model_Request $request
     * @param Mage_Api2_Model_Request $response
     */
    protected function _adminToken(Mage_Api2_Model_Request $request, Mage_Api2_Model_Response $response)
    {
        /** @var $authManager Oro_CrmIntegration_Model_Server_Auth */
        $authManager = Mage::getSingleton('oro_crmintegration/server_auth');
        $adminToken = $authManager->getAdminToken($request);
        $renderer = Mage_Api2_Model_Renderer::factory($request->getAcceptTypes());
        $response->setMimeType($renderer->getMimeType())
            ->setBody($renderer->render($adminToken));
    }
}
