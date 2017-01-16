<?php
/**
 * @category  Oro
 * @package   Oro_CrmIntegration
 * @copyright Copyright 2016 Oro Inc. (http://www.orocrm.com)
 */

$role = Mage::getModel('api2/acl_global_role')->load(Mage_Api2_Model_Acl_Global_Role::ROLE_GUEST_ID);
if ($role->getId()) {
    try {
        $aclRule = Mage::getModel('api2/acl_global_rule');
        $aclRule->addData(array(
            'role_id' => $role->getId(),
            'resource_id' => 'oro_admin_token',
            'privilege' => 'create'
        ));

        $aclRule->save();
    } catch(Exception $e) {
        Mage::logException($e);
    }
}
