<?php
/**
 * @category  Oro
 * @package   Oro_CrmIntegration
 * @copyright Copyright 2016 Oro Inc. (http://www.orocrm.com)
 */
class Oro_CrmIntegration_Model_Api2_Order_Rest_Admin_V1 extends Oro_CrmIntegration_Model_Api2_Resource
{
    protected $_ignoredAttributeCodes = array(
        'order'    =>  array('entity_id', 'attribute_set_id', 'entity_type_id')
    );

    protected $_addressFilter;
    protected $_orderItemFilter;
    protected $_paymentFilter;
    protected $_historyItemFilter;

    public function __construct()
    {
        parent::__construct();
        $this->_apiHelper = Mage::helper('oro_api');
    }

    /**
     * Retrieve information about customer
     *
     * @throws Mage_Api2_Exception
     * @return array
     */
    protected function _retrieve()
    {
        $incrementId = $this->getRequest()->getParam('increment_id');
        if ($incrementId == 'search') {
            return $this->_retrieveSearchedItems();
        } else {
            return $this->_retrieveSingleItem($incrementId);
        }
    }

    /**
     * @param $id
     * @return array
     */
    protected function _retrieveSingleItem($incrementId)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($incrementId);
        if (!$order->getId()) {
            $this->_critical(self::RESOURCE_NOT_FOUND);
        }

        return $this->_getOrderData($order);
    }

    /**
     * @return array
     */
    protected function _retrieveSearchedItems()
    {
        /** @var Mage_Sales_Model_Resource_Quote_Collection $collection */
        $orderCollection = $this->_getCollectionForRetrieve();
        $items = array();
        foreach ($orderCollection as $order) {
            $orderData = $this->_getOrderData($order);
            $items[] = $this->getFilter()->out($orderData);
        }

        return array(
            'items' => $items,
            'search_criteria' => $this->getSearchCriteria()->getFilters(),
            'total_count' => $orderCollection->getSize()
        );
    }

    /**
     * @return Object
     */
    protected function _getCollectionForRetrieve()
    {
        //TODO: add full name logic
        $billingAliasName  = 'billing_o_a';
        $shippingAliasName = 'shipping_o_a';

        /** @var $orderCollection Mage_Sales_Model_Mysql4_Order_Collection */
        $сollection = Mage::getResourceModel("sales/order_collection");

        $billingFirstnameField  = "$billingAliasName.firstname";
        $billingLastnameField   = "$billingAliasName.lastname";
        $shippingFirstnameField = "$shippingAliasName.firstname";
        $shippingLastnameField  = "$shippingAliasName.lastname";

        $сollection->addAttributeToSelect('*')
            ->addAddressFields()
            ->addExpressionFieldToSelect(
                'billing_firstname',
                "{{billing_firstname}}",
                array('billing_firstname' => $billingFirstnameField)
            )
            ->addExpressionFieldToSelect(
                'billing_lastname',
                "{{billing_lastname}}",
                array('billing_lastname' => $billingLastnameField)
            )
            ->addExpressionFieldToSelect(
                'shipping_firstname',
                "{{shipping_firstname}}",
                array('shipping_firstname' => $shippingFirstnameField)
            )
            ->addExpressionFieldToSelect(
                'shipping_lastname',
                "{{shipping_lastname}}",
                array('shipping_lastname' => $shippingLastnameField)
            )
            ->addExpressionFieldToSelect(
                'billing_name',
                "CONCAT({{billing_firstname}}, ' ', {{billing_lastname}})",
                array('billing_firstname' => $billingFirstnameField, 'billing_lastname' => $billingLastnameField)
            )
            ->addExpressionFieldToSelect(
                'shipping_name',
                'CONCAT({{shipping_firstname}}, " ", {{shipping_lastname}})',
                array('shipping_firstname' => $shippingFirstnameField, 'shipping_lastname' => $shippingLastnameField)
            );
        $this->_applyCollectionModifiersM2($сollection);

        return $сollection;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function _getOrderData($order)
    {
        /** @var array $orderData */
        $helper = Mage::helper('oro_crmintegration/api2');
        $orderData = $helper->m2RemapData($order->toArray(), $this->getResourceType(), $this->getUserType());
        $attributes = $this->_getNotIncludedAttributes($order, $orderData, $this->getFilter()->getAttributesToInclude());
        $this->_cleanupAdditionalAttributes('order', $attributes);
        if ($attributes) {
            $orderData['attributes'] = $attributes;
        }
        $orderData = array_merge($orderData, $this->_getOrderAdditionalInfo($order));

        return $orderData;
    }

    /**
     * Retrieve detailed order information
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function _getOrderAdditionalInfo($order)
    {
        if ($order->getGiftMessageId() > 0) {
            $order->setGiftMessage(
                Mage::getSingleton('giftmessage/message')->load($order->getGiftMessageId())->getMessage()
            );
        }

        $result = array();
        $shippingAddress = $order->getShippingAddress()->toArray();
        $billingAddress = $order->getBillingAddress()->toArray();
        $result['shipping_address'] = $this->_getAddressFilter()->out($shippingAddress);
        $result['billing_address'] = $this->_getAddressFilter()->out($billingAddress);
        $result['items'] = array();

        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllItems() as $item) {
            if ($item->getGiftMessageId() > 0) {
                $item->setGiftMessage(
                    Mage::getSingleton('giftmessage/message')->load($item->getGiftMessageId())->getMessage()
                );
            }

            $result['items'][] = $this->_getOrderItemFilter()->out($item->toArray());
        }

        $payment = $order->getPayment()->toArray();
        $result['payment'] = $this->_getPaymentFilter()->out($payment);

        $result['status_history'] = array();

        foreach ($order->getAllStatusHistory() as $history) {
            $historyItem = $history->toArray();
            $result['status_history'][] = $this->_getHistoryItemFilter()->out($historyItem);
        }

        $result['coupon_code'] = $order->getCouponCode();

        return $result;
    }

    /**
     * @return Mage_Api2_Model_Acl_Filter
     */
    protected function _getAddressFilter()
    {
        if (!$this->_addressFilter) {
            $self = clone $this;
            $self->setResourceType('oro_order_address');
            $filter = Mage::getModel('api2/acl_filter', $self);
            $this->_addressFilter = $filter;
        }

        return $this->_addressFilter;
    }

    /**
     * @return Mage_Api2_Model_Acl_Filter
     */
    protected function _getOrderItemFilter()
    {
        if (!$this->_orderItemFilter) {
            $self = clone $this;
            $self->setResourceType('oro_order_item');
            $filter = Mage::getModel('api2/acl_filter', $self);
            $this->_orderItemFilter = $filter;
        }

        return $this->_orderItemFilter;
    }

    /**
     * @return Mage_Api2_Model_Acl_Filter
     */
    protected function _getPaymentFilter()
    {
        if (!$this->_paymentFilter) {
            $self = clone $this;
            $self->setResourceType('oro_order_payment');
            $filter = Mage::getModel('api2/acl_filter', $self);
            $this->_paymentFilter = $filter;
        }

        return $this->_paymentFilter;
    }

    /**
     * @return Mage_Api2_Model_Acl_Filter
     */
    protected function _getHistoryItemFilter()
    {
        if (!$this->_historyItemFilter) {
            $self = clone $this;
            $self->setResourceType('oro_order_history_item');
            $filter = Mage::getModel('api2/acl_filter', $self);
            $this->_historyItemFilter = $filter;
        }

        return $this->_historyItemFilter;
    }
}
