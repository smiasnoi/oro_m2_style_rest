<?php
/**
 * @category  Oro
 * @package   Oro_CrmIntegration
 * @copyright Copyright 2016 Oro Inc. (http://www.orocrm.com)
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
     *
     */
    public function run()
    {
        parent::run();
        exit;
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
}
