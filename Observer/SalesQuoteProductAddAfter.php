<?php

namespace Klaviyo\Reclaim\Observer;

use Klaviyo\Reclaim\Helper\Data;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\CategoryFactory;

class SalesQuoteProductAddAfter implements ObserverInterface
{
    /**
     * Klaviyo Data Helper
     * @var Data
     */
    protected $_dataHelper;

    /**
     * Magento Category Factory
     * @var CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * @param Data $dataHelper
     * @param CategoryFactory $categoryFactory
     */
    public function __construct(
        Data $dataHelper,
        CategoryFactory $categoryFactory
    ) {
        $this->_dataHelper = $dataHelper;
        $this->_categoryFactory = $categoryFactory;
    }

    public function execute(Observer $observer)
    {
        $addedItems = $observer->getData('items');
        $quote = $addedItems[0]->getQuote();

        // Create a list of Simple Product Ids being added as part of a bundle.
        // We collect the Item name and Qty added to cart, to be sent as AddedItemBundleOptions with the payload.
        $childrenIds = [];

        foreach ($addedItems as $item) {
            if ($item->getProductType() == 'bundle') {
                $children = $item->getChildren();
                foreach ($children as $child) {
                    if (!in_array($child->getId(), $childrenIds)) {
                        array_push($childrenIds, $child->getId());
                    }
                }
            } elseif (!is_null($item->getParentItem()) && !in_array($item->getId(), $childrenIds)) { //If child comes  before Parent, collect those too
                array_push($childrenIds, $item->getId());
            }

            if (!in_array($item->getId(), $childrenIds)) {
                $this->klAddedToCartItemData($quote, $item);
            }
        }
    }

    /**
     * Build Added to Cart payload for current Added item
     * @param $quote
     * @param $addedItem
     */
    public function klAddedToCartItemData($quote, $addedItem)
    {
        $addedProduct = $addedItem->getProduct();
        $addedItemData = [
            'AddedItemCategories' => (array) $addedProduct->getCategoryIds(),
            'AddedItemImageUrlKey' => (string) is_null($addedProduct->getData('small_image')) ? "" : stripslashes($addedProduct->getData('small_image')),
            'AddedItemPrice' => (float) $addedProduct->getFinalPrice(),
            'AddedItemQuantity' => (int) $addedItem->getQty(),
            'AddedItemProductID' => (int) $addedProduct->getId(),
            'AddedItemProductName' => (string) $addedProduct->getName(),
            'AddedItemSku' => (string) $addedProduct->getSku(),
            'AddedItemUrl' => (string) is_null($addedProduct->getProductUrl()) ? "" : stripslashes($addedProduct->getProductUrl()),
        ];

        $klAddedToCartPayload = array_merge(
            $this->klBuildCartData($quote, $addedItem),
            $addedItemData
        );

        if ($addedItem->getProductType() == 'bundle') {
            $klAddedToCartPayload = array_merge(
                $klAddedToCartPayload,
                ['AddedItemBundleOptions' => $this->getBundleProductOptions($addedItem)]
            );
        }

        // Storing payload in the DataHelper object for SalesQuoteSaveAfter Observer since quoteId is not set at this point for guest checkouts
        $this->_dataHelper->setObserverAtcPayload($klAddedToCartPayload);
    }

    /**
     * Helper function to add items from cart to Added to Cart payload
     * @param $quote
     * @param $addedItem
     * @return array
     */
    public function klBuildCartData($quote, $addedItem)
    {
        $cartItems = $quote->getAllVisibleItems() ?? [];
        $cartQty = 0;
        $items = [];
        $cartItemNames = [];
        $cartItemCategories = [];

        foreach ($cartItems as $item) {
            $product = $item->getProduct();
            $cartItemId = $product->getId();
            $itemCategories = $product->getCategoryIds();
            $itemName = $item->getName();
            $currentProduct = [
                'Categories' => (array) $itemCategories,
                'ImageUrlKey' => (string) is_null($product->getData('small_image')) ? "" : stripslashes($product->getData('small_image')),
                'ProductId' => (int) $cartItemId,
                'Price' => (float) $product->getFinalPrice(),
                'Title' => (string) $itemName,
                'Url' => (string) is_null($product->getProductUrl()) ? "" : stripslashes($product->getProductUrl()),
                'Quantity' => (int) $item->getQty()
            ];
            $cartQty += $item->getQty();
            array_push($items, $currentProduct);
            array_push($cartItemNames, $itemName);
            $cartItemCategories = $this->uniqueArrayOfStrings($cartItemCategories, $itemCategories);
        }

        return [
            '$value' => (float) $quote->getBaseGrandTotal() + $addedItem->getPrice(),
            'ItemNames' => (array) $cartItemNames,
            'Items' => (array) $items,
            'ItemCount' => (int) $cartQty,
            'Categories' => (array) $cartItemCategories,
            'time' => time()
        ];
    }

    /**
     * Helper function to ensure no duplicates in array
     * @param $array_one
     * @param $array_two
     * @return array
     */
    public function uniqueArrayOfStrings($array_one, $array_two): array
    {
        return array_values(array_unique(
            array_merge($array_one, $array_two),
            SORT_REGULAR
        )) ;
    }

    /**
     * Helper function to get Simple Product Quantities and Names for Bundled Product added to cart
     * @param $addedItem
     * @return array
     */
    public function getBundleProductOptions($addedItem): array
    {
        $productOptions = $addedItem->getChildren();
        $bundleOptionsData = [];

        foreach ($productOptions as $option) {
            $productName = $option->getName();
            $productQty = $option->getQty();
            array_push(
                $bundleOptionsData,
                [
                    'Option Name' => $productName,
                    'Option Qty' => $productQty
                ]
            );
        }

        return $bundleOptionsData;
    }
}
