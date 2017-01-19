<?php
/**
 * @category  Oro
 * @package   Oro_CrmIntegration
 * @copyright Copyright 2016 Oro Inc. (http://www.orocrm.com)
 */
class Oro_CrmIntegration_Model_Api2_Quote_Rest_Admin_V1 extends Oro_CrmIntegration_Model_Api2_Resource
{
    protected $_ignoredAttributeCodes = array(
        'quote'    =>  array('attribute_set_id', 'entity_type_id')
    );

    protected $_addressFilter;
    protected $_quoteItemFilter;
    protected $_paymentFilter;

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
        $id = $this->getRequest()->getParam('id');
        if ($id == 'search') {
            return $this->_retrieveSearchedItems();
        } else {
            return $this->_retrieveSingleItem($id);
        }
    }

    /**
     * @param $id
     * @return array
     */
    protected function _retrieveSingleItem($id)
    {
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getModel('sales/quote');
        $quote->loadByIdWithoutStore($id);
        if (!$quote->getId()) {
            $this->_critical(self::RESOURCE_NOT_FOUND);
        }

        return  $this->info($quote);
    }

    /**
     * @return array
     */
    protected function _retrieveSearchedItems()
    {
        /** @var Mage_Sales_Model_Resource_Quote_Collection $collection */
        $quoteCollection = $this->_getCollectionForRetrieve();
        $this->_preparesGiftMessages($quoteCollection);

        $items = array();
        /** @var Mage_Sales_Model_Quote $quote */
        foreach ($quoteCollection as $quote) {
            $row = $this->info($quote);
            $items[] = $this->getFilter()->out($row);
        }

        return array(
            'items' => $items,
            'search_criteria' => $this->getSearchCriteria()->getFilters(),
            'total_count' => $quoteCollection->getSize()
        );
    }

    /**
     * @return Object
     */
    protected function _getCollectionForRetrieve()
    {
        $collection = Mage::getResourceModel('sales/quote_collection');
        $this->_applyCollectionModifiersM2($collection);

        return $collection;
    }

    /**
     * Retrieve full information about quote
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return array
     */
    protected function info($quote)
    {
        $helper = Mage::helper('oro_crmintegration/api2');
        $result = $helper->m2RemapData($quote->toArray(), $this->getResourceType(), $this->getUserType());
        $attributes = $this->_getNotIncludedAttributes($quote, $result, $this->getFilter()->getAttributesToInclude());
        $this->_cleanupAdditionalAttributes('quote', $attributes);
        if ($attributes) {
            $result['attributes'] = $attributes;
        }

        $shippingAddres = $quote->getShippingAddress()->toArray();
        $billingAddress = $quote->getBillingAddress()->toArray();
        $result['shipping_address'] = $this->_getAddressFilter()->out($shippingAddres);
        $result['billing_address']  = $this->_getAddressFilter()->out($billingAddress);

        $result['items'] = array();
        /** @var Mage_Sales_Model_Quote_Item $item */
        foreach ($quote->getAllItems() as $item) {
            $quoteItem = $item->toArray();
            $productAttributes = $this->_getProductAttributes($item);
            $quoteItem = array_merge($quoteItem, $productAttributes);

            $result['items'][] = $this->_getQuoteItemFilter()->out($quoteItem);
        }

        $payment = $quote->getPayment()->toArray();
        $result['payment'] = $this->_getPaymentFilter()->out($payment);
        if (isset($result['payment'], $result['payment']['additional_information'])
            && is_array($result['payment']['additional_information'])
        ) {
            $result['payment']['additional_information'] = serialize($result['payment']['additional_information']);
        }

        return $result;
    }

    /**
     * @param Mage_Sales_Model_Quote_Item $item
     * @return array
     */
    protected function _getProductAttributes($item)
    {
        $result = array();
        $product = $item->getProduct();

        if ($product) {
            $productImage = $product->getData('image');
            if ($productImage) {
                $result['product_image_url'] = Mage::getSingleton('catalog/product_media_config')
                    ->getMediaUrl($productImage);
            }
            $result['product_url'] = $product->getProductUrl(false);
        }

        return $result;
    }

    /**
     * Set gift_message key to quote and quote item
     *
     * @param Mage_Sales_Model_Resource_Quote_Collection $quoteCollection
     */
    protected function _preparesGiftMessages($quoteCollection)
    {
        $messageIds = array();
        /* @var Mage_Sales_Model_Quote $quote */
        foreach ($quoteCollection as $quote) {
            if ($quote->getGiftMessageId()) {
                $messageIds[] = $quote->getGiftMessageId();
            }
            foreach ($quote->getAllItems() as $quoteItem) {
                if ($quoteItem->getGiftMessageId()) {
                    $messageIds[] = $quoteItem->getGiftMessageId();
                }
            }
        }

        if (!$messageIds) {
            return;
        }

        $messageIds = array_unique($messageIds);

        $giftCollection = Mage::getResourceModel('giftmessage/message_collection');
        $giftCollection->addFieldToFilter('gift_message_id', array('in' => $messageIds));

        /* @var Mage_Sales_Model_Quote $quote */
        foreach ($quoteCollection as $quote) {
            if ($quote->getGiftMessageId()) {
                $quote->setGiftMessage($giftCollection->getItemById($quote->getGiftMessageId())->getMessage());
            }
            foreach ($quote->getAllItems() as $quoteItem) {
                if ($quoteItem->getGiftMessageId()) {
                    $quoteItem->setGiftMessage($giftCollection->getItemById($quoteItem->getGiftMessageId())->getMessage());
                }
            }
        }
    }

    /**
     * @return Mage_Api2_Model_Acl_Filter
     */
    protected function _getAddressFilter()
    {
        if (!$this->_addressFilter) {
            $self = clone $this;
            $self->setResourceType('oro_quote_address');
            $filter = Mage::getModel('api2/acl_filter', $self);
            $this->_addressFilter = $filter;
        }

        return $this->_addressFilter;
    }

    /**
     * @return Mage_Api2_Model_Acl_Filter
     */
    protected function _getQuoteItemFilter()
    {
        if (!$this->_quoteItemFilter) {
            $self = clone $this;
            $self->setResourceType('oro_quote_item');
            $filter = Mage::getModel('api2/acl_filter', $self);
            $this->_quoteItemFilter = $filter;
        }

        return $this->_quoteItemFilter;
    }

    /**
     * @return Mage_Api2_Model_Acl_Filter
     */
    protected function _getPaymentFilter()
    {
        if (!$this->_paymentFilter) {
            $self = clone $this;
            $self->setResourceType('oro_quote_payment');
            $filter = Mage::getModel('api2/acl_filter', $self);
            $this->_paymentFilter = $filter;
        }

        return $this->_paymentFilter;
    }
}
