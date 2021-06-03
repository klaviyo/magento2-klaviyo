<?php


namespace Klaviyo\Reclaim\Observer;

use Klaviyo\Reclaim\Plugin\Api\CartSearchRepository;

use Magento\Framework\Config\Scope;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Checkout\Model\Session;

class SalesQuoteProductAddAfter implements ObserverInterface
{
    /**
     * Klaviyo Cart Search Interface
     * @var  CartSearchRepository $_cartSearchRepository
     */
    protected $_cartSearchRepository;

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
     * @param CartSearchRepository $cartSearchRepository
     * @param CategoryFactory $categoryFactory
     * @param Session $checkoutsession
     */
    public function __construct(
        CartSearchRepository $cartSearchRepository,
        CategoryFactory $categoryFactory,
        Session $checkoutsession
    )
    {
        $this->_cartSearchRepository = $cartSearchRepository;
        $this->_categoryFactory = $categoryFactory;
        $this->_checkoutSession = $checkoutsession;
    }

    public function execute(Observer $observer)
    {
        $quote = $observer->getData('items')[0]->getQuote();
        $addedItems = $observer->getData('items');

        foreach ( $addedItems as $item ){
            $this->klAddedToCartItemData( $quote, $item );
        }
    }

    /**
     * Build Added to Cart payload for current Added item
     * @param $quote
     * @param $addedItem
     */
    public function klAddedToCartItemData( $quote, $addedItem )
    {
        $addedProduct = $addedItem->getProduct();
        $addedItemData = array(
            'AddedItemCategories' => (array) $this->getCategoryName( $addedProduct->getCategoryIds() ),
            'AddedItemDescription' => (string) strip_tags( $addedProduct->getDescription() ),
            'AddedItemImageUrlKey' => (string) $addedProduct->getData('small_image'),
            'AddedItemPrice' => (float) $addedProduct->get_price(),
            'AddedItemQuantity' => (int) $addedItem->getQty(),
            'AddedItemProductID' => (int) $addedProduct->getId(),
            'AddedItemProductName' => (string) $addedItem->getName(),
            'AddedItemSku' => (string) $addedProduct->getSku(),
            'AddedItemUrl' => (string) $addedProduct->getProductUrl()
        );

        $klAddedToCartPayload = array_merge( $this->klBuildCartData( $quote, $addedItem ), $addedItemData );

        $this->getCheckoutSession()->setKlAddedToCartKey( $klAddedToCartPayload );
    }

    /**
     * Helper function to add items from cart to Added to Cart payload
     * @param $quote
     * @param $addedItem
     * @return array
     */
    public function klBuildCartData( $quote, $addedItem )
    {
        $cartItems = $quote->getItems() ? $quote->getitems() : array();
        $cartQty = 0;
        $items = array();
        $cartItemNames = array();
        $cartItemCategories = array();

        foreach( $cartItems as $item ) {
            $product = $item->getProduct();
            $cartItemId = $product->getId();
            $itemCategories = $this->getCategoryName( $product->getCategoryIds() );
            $itemName = $item->getName();
            $currentProduct = array(
                'Categories' => (array) $itemCategories,
                'ImageUrlKey' => (string) $product->getData('small_image'),
                'ProductId' => (int) $cartItemId,
                'Price' => (float) $product->getPrice(),
                'Title' => (string) $itemName,
                'Description' => (string) strip_tags( $product->getDescription() ),
                'Url' => (string) $product->getProductUrl(),
                'Quantity' => (int) $item->getQty()
            );
            $cartQty += $item->getQty();
            array_push( $items, $currentProduct );
            array_push( $cartItemNames, $itemName );
            $cartItemCategories = $this->uniqueArrayOfStrings( $cartItemCategories, $itemCategories );
        }

        return array(
            '$value' => (float) $quote->getBaseGrandTotal() + $addedItem->getPrice(),
            'ItemNames' => (array) $cartItemNames,
            'Items' => (array) $items,
            'ItemCount' => (int) $cartQty,
            'Categories' => (array) $cartItemCategories,
            '$service' => 'magento_two'
        );
    }

    /**
     * Retrieves category names from category IDs
     * @param array $categoryIds
     * @return array
     */
    public function getCategoryName( array $categoryIds )
    {
        $categoryNames = [];
        foreach ( $categoryIds as $id ) {
            $category = $this->_categoryFactory->create()->load( $id );
            $categoryName = $category->getName();
            array_push( $categoryNames, $categoryName );
        }

        return $categoryNames;
    }

    public function uniqueArrayOfStrings( $array_one, $array_two ): array
    {
        return array_values( array_unique( array_merge( $array_one, $array_two ), SORT_REGULAR ) ) ;
    }

    public function getCheckoutSession(){
        return $this->_checkoutSession;
    }
}
