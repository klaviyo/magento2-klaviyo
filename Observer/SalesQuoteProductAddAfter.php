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
    )
    {
        $this->_dataHelper = $dataHelper;
        $this->_categoryFactory = $categoryFactory;
    }

    public function execute(Observer $observer)
    {
        $quote = $observer->getData('items')[0]->getQuote();
        $addedItems = $observer->getData('items');
        $childrenIds = [];

        foreach ($addedItems as $item){
            if ($item->getProductType() == 'bundle') {
                $children = $item->getChildren();
                foreach ($children as $child){
                    array_push($childrenIds, $child->getId());
                }
            }

            if (in_array( $item->getId(), $childrenIds )){
                return;
            } else {
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
            'AddedItemCategories' => (array) $this->getCategoryName($addedProduct->getCategoryIds()),
            'AddedItemDescription' => (string) strip_tags($addedProduct->getDescription() ?? $addedItem->getDescription()),
            'AddedItemImageUrlKey' => (string) stripslashes($addedProduct->getData('small_image')),
            'AddedItemPrice' => (float) $addedProduct->getFinalPrice(),
            'AddedItemQuantity' => (int) $addedItem->getQty(),
            'AddedItemProductID' => (int) $addedProduct->getId(),
            'AddedItemProductName' => (string) $addedProduct->getName(),
            'AddedItemSku' => (string) $addedProduct->getSku(),
            'AddedItemUrl' => (string) stripslashes($addedProduct->getProductUrl())
        ];

        $klAddedToCartPayload = array_merge(
            $this->klBuildCartData( $quote, $addedItem ),
            $addedItemData
        );

        if ($addedItem->getProductType() == 'bundle'){
            $klAddedToCartPayload = array_merge(
                $klAddedToCartPayload,
                ['AddedItemBundleOptions' => $this->getBundleProductOptions($addedItem)]
            );
        }

        // Storing payload in the DataHelper object for SalesQuoteSaveAfter Observer since quoteId is not set at this point for guest checkouts
        $this->_dataHelper->tempPayload = $klAddedToCartPayload;
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

        foreach($cartItems as $item) {
            $product = $item->getProduct();
            $cartItemId = $product->getId();
            $itemCategories = $this->getCategoryName($product->getCategoryIds());
            $itemName = $item->getName();
            $currentProduct = [
                'Categories' => (array) $itemCategories,
                'ImageUrlKey' => (string) stripslashes($product->getData('small_image')),
                'ProductId' => (int) $cartItemId,
                'Price' => (float) $product->getPrice(),
                'Title' => (string) $itemName,
                'Description' => (string) strip_tags($product->getDescription() ?? $item->getDescription()),
                'Url' => (string) stripslashes($product->getProductUrl()),
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
            'Categories' => (array) $cartItemCategories
        ];
    }

    public function getCategoryName($categoryIds)
    {
        $categoryFactory = $this->_categoryFactory->create();
        $categoryNames = [];
        foreach ( $categoryIds as $id ) {
            $category = $categoryFactory->load($id);
            $categoryName = $category->getName();
            array_push($categoryNames, $categoryName);
        }

        return $categoryNames;
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

    public function getBundleProductOptions($addedItem)
    {
        $productOptions = $addedItem->getChildren();
        $bundleOptionsData = [];

        foreach ($productOptions as $option){
            $productName = $option->getName();
            $productQty = $option->getQty();
            array_push($bundleOptionsData,
                [
                    'Option Name' => $productName,
                    'Option Qty' => $productQty
                ]);
        }

        return $bundleOptionsData;
    }
}
