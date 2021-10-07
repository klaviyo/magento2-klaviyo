<?php


namespace Klaviyo\Reclaim\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Checkout\Model\Session;

class SalesQuoteProductAddAfter implements ObserverInterface
{
    /**
     * Magento Category Factory
     * @var CategoryFactory $_categoryFactory
     */
    protected $_categoryFactory;

    /**
     * Magento Checkout Session data
     * @var Session $_checkoutSession
     */
    protected $_checkoutSession;

    /**
     * SalesQuoteProductAddAfter constructor.
     * @param CategoryFactory $categoryFactory
     * @param Session $checkoutsession
     */
    public function __construct(
        CategoryFactory $categoryFactory,
        Session $checkoutsession
    )
    {
        $this->_categoryFactory = $categoryFactory;
        $this->_checkoutSession = $checkoutsession;
    }

    public function execute(Observer $observer)
    {
        $quote = $observer->getData('items')[0]->getQuote();
        $addedItems = $observer->getData('items');

        foreach ($addedItems as $item){
            $this->klAddedToCartItemData($quote, $item);
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
        $addedItemData = array(
            'AddedItemCategories' => (array) $this->getCategoryName($addedProduct->getCategoryIds()),
            'AddedItemDescription' => (string) strip_tags($addedProduct->getDescription()),
            'AddedItemImageUrlKey' => (string) $addedProduct->getData('small_image'),
            'AddedItemPrice' => (float) $addedProduct->get_price(),
            'AddedItemQuantity' => (int) $addedItem->getQty(),
            'AddedItemProductID' => (int) $addedProduct->getId(),
            'AddedItemProductName' => (string) $addedItem->getName(),
            'AddedItemSku' => (string) $addedProduct->getSku(),
            'AddedItemUrl' => (string) $addedProduct->getProductUrl()
        );

        $klAddedToCartPayload = array_merge(
            $this->klBuildCartData( $quote, $addedItem ),
            $addedItemData
        );

        // Creating a custom session variable in the checkout session since quoteId is not set at this point for guest checkouts
        $this->getCheckoutSession()->setKlAddedToCartKey( $klAddedToCartPayload );
    }

    /**
     * Helper function to add items from cart to Added to Cart payload
     * @param $quote
     * @param $addedItem
     * @return array
     */
    public function klBuildCartData($quote, $addedItem)
    {
        $cartItems = $quote->getItems() ?? array();
        $cartQty = 0;
        $items = array();
        $cartItemNames = array();
        $cartItemCategories = array();

        foreach($cartItems as $item) {
            $product = $item->getProduct();
            $cartItemId = $product->getId();
            $itemCategories = $this->getCategoryName($product->getCategoryIds());
            $itemName = $item->getName();
            $currentProduct = array(
                'Categories' => (array) $itemCategories,
                'ImageUrlKey' => (string) $product->getData('small_image'),
                'ProductId' => (int) $cartItemId,
                'Price' => (float) $product->getPrice(),
                'Title' => (string) $itemName,
                'Description' => (string) strip_tags($product->getDescription()),
                'Url' => (string) $product->getProductUrl(),
                'Quantity' => (int) $item->getQty()
            );
            $cartQty += $item->getQty();
            array_push($items, $currentProduct);
            array_push($cartItemNames, $itemName);
            $cartItemCategories = $this->uniqueArrayOfStrings($cartItemCategories, $itemCategories);
        }

        return array(
            '$value' => (float) $quote->getBaseGrandTotal() + $addedItem->getPrice(),
            'ItemNames' => (array) $cartItemNames,
            'Items' => (array) $items,
            'ItemCount' => (int) $cartQty,
            'Categories' => (array) $cartItemCategories
        );
    }

    /**
     * Retrieves category names from category IDs
     * @param array $categoryIds
     * @return array
     */
    public function getCategoryName(array $categoryIds)
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

    /**
     * Returns active checkout session
     * @return Session
     */
    public function getCheckoutSession(){
        return $this->_checkoutSession;
    }
}
