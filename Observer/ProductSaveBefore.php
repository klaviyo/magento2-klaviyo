<?php

namespace Klaviyo\Reclaim\Observer;

use Exception;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Helper\Webhook;
use Klaviyo\Reclaim\Helper\Logger;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;


class ProductSaveBefore implements ObserverInterface
{
    /**
     * Klaviyo scope setting helper
     * @var ScopeSetting $klaviyoScopeSetting
     */
    protected $_klaviyoScopeSetting;

    /**
     * Klaviyo logger helper
     * @var \Klaviyo\Reclaim\Helper\Logger $klaviyoLogger
     */
    protected $_klaviyoLogger;

    /**
     * @var Webhook $webhookHelper
     */
    protected  $_webhookHelper;

    protected $_categoryFactory;
    protected $product_category_names = [];

    /**
     * @param Webhook $webhookHelper
     * @param ScopeSetting $klaviyoScopeSetting
     * @param CategoryFactory $categoryFactory
     * @param Logger $klaviyoLogger
     */
    public function __construct(
        Webhook $webhookHelper,
        ScopeSetting $klaviyoScopeSetting,
        CategoryFactory $categoryFactory,
        Logger $klaviyoLogger
    ) {
        $this->_webhookHelper = $webhookHelper;
        $this->_klaviyoScopeSetting = $klaviyoScopeSetting;
        $this->_categoryFactory = $categoryFactory;
        $this->categories = [];
        $this->_klaviyoLogger = $klaviyoLogger;
    }

    /**
     * customer register event handler
     *
     * @param Observer $observer
     * @return void
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $product_type = $product->getTypeId();
        if ($product_type == 'grouped' || $product_type == 'bundle' || $product_type == 'downloadable' || $product_type == 'virtual') {
          return;
        }

        $storeIds = $product->getStoreIds();
        $storeIdKlaviyoMap = $this->_klaviyoScopeSetting->getStoreIdKlaviyoAccountSetMap($storeIds);

        foreach ($storeIdKlaviyoMap as $klaviyoId => $storeIds) {
            if (empty($storeIds)) {
                continue;
            }

            if ($this->_klaviyoScopeSetting->getWebhookSecret() && $this->_klaviyoScopeSetting->getProductSaveBeforeSetting($storeIds[0])) {

              $product_category_ids = $product->getCategoryIds();
              $cat_factory = $this->_categoryFactory->create();
              foreach ($product_category_ids as $category_id) {
                $category = $cat_factory->load($category_id);
                $this->categories[] = $category->getName();
              }

              $data = array (
                  'store_ids' => $storeIds,
                  'id' => $product->getId(),
                  'title' => $product->getName(),
                  'price' => $product->getPrice(),
                  'special_price' => $product->getSpecialPrice(),
                  'special_from_date' => $product->getSpecialFromDate(),
                  'special_to_date' => $product->getSpecialToDate(),
                  'categories' => $this->categories,
                  'sku' => $product->getSku(),
                  'qty' => $product->getExtensionAttributes()->getStockItem()->getQty(),
                  'visibility' => $product->getVisibility(),
                  'isInStock' => $product->isInStock(),
                  'createdAt' => $product->getCreatedAt(),
                  'updatedAt' => $product->getUpdatedAt(),
                  'status' => $product->getStatus(),
                  'url' => $product->getUrlKey(),
                  'image_url' => $product->getImage(),
                  'image_thumbnail' => $product->getThumbnail()
              );

              // If Item Visibility is 1 DON'T SEND THAT SHIT?? or do and unpublish?

              $this->_webhookHelper->makeWebhookRequest('product/save', $data, $klaviyoId);
            }
        }
    }
}
