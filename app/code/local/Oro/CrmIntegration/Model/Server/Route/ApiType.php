<?php
/**
 * @category  Oro
 * @package   Oro_CrmIntegration
 * @copyright Copyright 2016 Oro Inc. (http://www.orocrm.com)
 */
class Oro_CrmIntegration_Model_Server_Route_ApiType
    extends Mage_Api2_Model_Route_Abstract
    implements Mage_Api2_Model_Route_Interface
{
    /**
     * API url template with API type variable
     */
    const API_ROUTE = 'rest/V1';

    /**
     * Prepares the route for mapping by splitting (exploding) it
     * to a corresponding atomic parts. These parts are assigned
     * a position which is later used for matching and preparing values.
     *
     * @param string $route Map used to match with later submitted URL path
     * @param array $defaults Defaults for map variables with keys as variable names
     * @param array $reqs Regular expression requirements for variables (keys as variable names)
     * @param Zend_Translate $translator Translator to use for this instance
     * @param mixed $locale
     */
    public function __construct($route, $defaults = array(), $reqs = array(), Zend_Translate $translator = null,
                                $locale = null
    ) {
        $_defaults = array_merge($defaults, array('api_type' => 'rest'));
        parent::__construct(
            array(
                Mage_Api2_Model_Route_Abstract::PARAM_ROUTE => self::API_ROUTE,
                Mage_Api2_Model_Route_Abstract::PARAM_DEFAULTS => $_defaults
            ));
    }
}
