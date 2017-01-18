<?php
/**
 * @category  Oro
 * @package   Oro_CrmIntegration
 * @copyright Copyright 2016 Oro Inc. (http://www.orocrm.com)
 */
class Oro_CrmIntegration_Model_Api2_Quote_List_Rest_Admin_V1 extends Oro_CrmIntegration_Model_Api2_Resource
{
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
        /** @var Mage_Sales_Model_Resource_Quote_Collection $collection */
        $collection = $this->_getCollectionForRetrieve();
        $this->_preparesGiftMessages($collection);

        $items = array();
        /** @var Mage_Sales_Model_Quote $quote */
        foreach ($collection as $quote) {
            $row = $this->info($quote);
            $items[] = $row;
        }

        return array(
            'items' => $items,
            'search_criteria' => $this->getSearchCriteria()->getFilters(),
            'total_count' => $collection->getSize()
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
        $result = $quote->__toArray();
        $result['shipping_address'] = $quote->getShippingAddress()->__toArray();
        $result['billing_address']  = $quote->getBillingAddress()->__toArray();
        $result['items'] = array();

        /** @var Mage_Sales_Model_Quote_Item $item */
        foreach ($quote->getAllItems() as $item) {
            $quoteItem = $item->__toArray();
            $productAttributes = $this->_getProductAttributes($item);
            $quoteItem = array_merge($quoteItem, $productAttributes);

            $result['items'][] = $quoteItem;
        }

        $result['payment'] = $quote->getPayment()->__toArray();
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
}
