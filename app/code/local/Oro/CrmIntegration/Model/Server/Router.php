<?php
/**
 * @category  Oro
 * @package   Oro_CrmIntegration
 * @copyright Copyright 2016 Oro Inc. (http://www.orocrm.com)
 */
class Oro_CrmIntegration_Model_Server_Router extends Mage_Api2_Model_Router
{
    /**
     * Set API type to request as a result of one pass route
     *
     * @param Mage_Api2_Model_Request $request
     * @param boolean $trimApiTypePath OPTIONAL If TRUE - /api/:api_type part of request path info will be trimmed
     * @return Mage_Api2_Model_Router
     * @throws Mage_Api2_Exception
     */
    public function routeApiType(Mage_Api2_Model_Request $request, $trimApiTypePath = true)
    {
        /** @var $apiTypeRoute Oro_CrmIntegration_Model_Server_Route_ApiType */
        $apiTypeRoute = Mage::getModel('oro_crmintegration/server_route_apiType');

        if (!($apiTypeMatch = $apiTypeRoute->match($request, true))) {
            throw new Mage_Api2_Exception('Request does not match type route.', Mage_Api2_Model_Server::HTTP_NOT_FOUND);
        }
        // Trim matched URI path for next routes
        if ($trimApiTypePath) {
            $matchedPathLength = strlen('/' . ltrim($apiTypeRoute->getMatchedPath(), '/'));

            $request->setPathInfo(substr($request->getPathInfo(), $matchedPathLength));
        }
        $request->setParam('api_type', $apiTypeMatch['api_type']);

        return $this;
    }
}
