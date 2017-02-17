<?php

namespace Klaviyo\Reclaim\Block\Catalog\Product;

class ViewedProduct extends \Magento\Framework\View\Element\Template
{
    protected $_helper;
    protected $_objectManager;
    protected $_registry;
    protected $_categoryFactory;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Klaviyo\Reclaim\Helper\Data $helper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_helper = $helper;
        $this->_objectManager = $objectManager;
        $this->_registry = $registry;
        $this->_categoryFactory = $categoryFactory;
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
        return $this->_helper->getPublicApiKey();
    }

    /**
     * Grab whether the Klaviyo_Reclaim extension is enabled through Admin from
     * the configuration helper and return it.
     *
     * @return boolean
     */
    public function isKlaviyoEnabled()
    {
        return $this->_helper->getEnabled();
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
     * @return JSON
     */
    public function getProductCategoriesAsJson()
    {
        $categories = array();
        foreach ($this->getProduct()->getCategoryIds() as $category_id) {
          $category = $category = $this->_categoryFactory->create()->load($category_id);
          $categories[] = $category->getName();
        }
        return json_encode($categories);
    }
}
