<?php

namespace Klaviyo\Reclaim\Block\Catalog\Product;

use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Helper\Data;
use Magento\Catalog\Helper\Image;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class ViewedProduct extends Template
{
    protected $_klaviyoScopeSetting;
    protected $_registry;
    protected $imageUrl = null;
    protected $categories = [];
    protected $price = 0;
    const INTEGRATION_KEY = 'magento_two';

    /**
     * @var Magento\Catalog\Helper\Image
     */
    private $imageHelper;

    /**
     * ViewedProduct constructor.
     * @param Context $context
     * @param ScopeSetting $klaviyoScopeSetting
     * @param Registry $registry
     * @param Image $imageHelper
     * @param Data $dataHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        ScopeSetting $klaviyoScopeSetting,
        Registry $registry,
        Image $imageHelper,
        Data $dataHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_klaviyoScopeSetting = $klaviyoScopeSetting;
        $this->_registry = $registry;
        $this->imageHelper = $imageHelper;
        $this->_dataHelper = $dataHelper;
    }

    /**
     * Grab the Klaviyo public API key from the configuration helper and return it.
     * Used to make `identify` calls for `Active on Site` metric (for signed in users)
     * and `track` calls for `Viewed Product` metrics.
     *
     * @return string
     */
    public function getPublicApiKey()
    {
        return $this->_klaviyoScopeSetting->getPublicApiKey();
    }

    /**
     * Grab whether the Klaviyo_Reclaim extension is enabled through Admin from
     * the configuration helper and return it.
     *
     * @return boolean
     */
    public function isKlaviyoEnabled()
    {
        return $this->_klaviyoScopeSetting->isEnabled();
    }

    /**
     * View helper to return the currently viewed catalog product. Used to track
     * the `Viewed Product` metric.
     *
     * @return Catalog_Product
     */
    public function getProduct()
    {
        return $this->_registry->registry('current_product');
    }

    /**
     * View helper to return a list of category names for the currently viewed
     * catalog product. Used to track the `Viewed Product` metric.
     *
     * @return array
     */
    public function getProductCategories()
    {
        if (empty($this->categories)) {
            $this->categories = $this->getProduct()
                ->getCategoryCollection()
                ->addAttributeToSelect('name')
                ->getColumnValues('name');
        }

        return $this->categories;
    }

    /**
     * View helper to return a list of category names for the currently viewed
     * catalog product. Used to track the `Viewed Product` metric.
     *
     * @return JSON
     */
    public function getProductCategoriesAsJson()
    {
        return json_encode($this->getProductCategories());
    }

    /**
     * Get Price
     *
     * @return double
     */
    public function getPrice()
    {
        if (!$this->price) {
            $_product = $this->getProduct();
            $this->price = $_product->getPrice();
            if ($_product->getTypeId() == 'grouped') {
                # if on a grouped product page, use the price of the lowest priced item in the group
                $associatedProducts = $_product->getTypeInstance()->getAssociatedProducts($_product);
                foreach ($associatedProducts as $associatedProduct) {
                    $associatedItemPrice = $associatedProduct->getPrice();
                    if ($this->price == 0) {
                        $this->price = $associatedItemPrice;
                    }

                    if ($this->price > $associatedItemPrice) {
                        $this->price = $associatedItemPrice;
                    }
                }
            }


            if ($_product->getTypeId() == 'configurable') {
                $_children = $_product->getTypeInstance()->getUsedProducts($_product);
                foreach ($_children as $child) {
                    $this->price = $child->getPrice();
                    if ($this->price) {
                        break;
                    }
                }
            }
        }

        if (!$this->price) {
            // as a fallback, return 0 in consistent format
            return number_format(0, 2);
        }


        return number_format($this->price, 2);
    }

    /**
     * Get Final Price
     *
     * @return double
     */
    public function getFinalPrice()
    {
        return  number_format($this->getProduct()->getPriceInfo()->getPrice('final_price')->getValue(), 2);
    }

    public function getProductImage()
    {
        if (!$this->imageUrl) {
            $this->imageUrl = $this->imageHelper
                ->init($this->getProduct(), 'product_base_image')
                ->getUrl();
        }

        return $this->imageUrl;
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!($this->isKlaviyoEnabled() && $this->getPublicApiKey())) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * @return string
     */
    public function getViewedProductJson()
    {
        $external_catalog_id = $this->_dataHelper->getExternalCatalogIdForEvent(
            $this->_storeManager->getStore()->getWebsiteId(),
            $this->_storeManager->getStore()->getId()
        );

        $_product = $this->getProduct();

        $result = [
            'external_catalog_id' => $external_catalog_id,
            'integration_key' => self::INTEGRATION_KEY,
            'ProductID' => $_product->getId(),
            'Name' => $_product->getName(),
            'SKU' => $_product->getSku(),
            'URL' => $_product->getProductUrl(),
            'Price' => $this->getPrice(),
            'FinalPrice' => $this->getFinalPrice(),
            'Categories' => $this->getProductCategories(),
            'StoreId' => $this->_klaviyoScopeSetting->storeId,
            '$value' => str_replace(",", "", $this->getPrice())
        ];

        if ($this->getProductImage()) {
            $result['ImageURL'] = $this->getProductImage();
        }

        return json_encode($result);
    }

    /**
     * @return string
     */
    public function getViewedItemJson()
    {
        $_product = $this->getProduct();

        $result = [
            'Title' => $_product->getName(),
            'ItemId' => $_product->getId(),
            'Url' => $_product->getProductUrl(),
            'Categories' => $this->getProductCategories(),
            'Metadata' => array(
                'Price' => $this->getPrice()
            ),
            'StoreId' => $this->_klaviyoScopeSetting->storeId
        ];

        if ($this->getProductImage()) {
            $result['ImageURL'] = $this->getProductImage();
        }

        return json_encode($result);
    }
}
