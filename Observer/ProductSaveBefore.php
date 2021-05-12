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
    protected $_webhookHelper;
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
        if ($product_type == 'grouped' || $product_type == 'bundle' || $product_type == 'downloadable') {
          return;
        }

        $storeIds = $product->getStoreIds();
        $storeIdKlaviyoMap = $this->_klaviyoScopeSetting->getStoreIdKlaviyoAccountSetMap($storeIds);

        foreach ($storeIdKlaviyoMap as $klaviyoId => $storeIds) {
            if (empty($storeIds)) {
                continue;
            }

            if ($this->_klaviyoScopeSetting->getWebhookSecret() && $this->_klaviyoScopeSetting->getProductSaveBeforeSetting($storeIds[0])) {

              $product_id = $product->getId();

              $product_category_ids = $product->getCategoryIds();
              $cat_factory = $this->_categoryFactory->create();
              foreach ($product_category_ids as $category_id) {
                $category = $cat_factory->load($category_id);
                $this->categories[] = $category->getName();
              }

            $metadata = array(
              'price' => $product->getPrice(),
              'sku' => $product->getSku()
            );

            if ($product->getSpecialPrice()) {
              $metadata['special_price'] = $product->getSpecialPrice();
              $metadata['special_from_date'] = $product->getSpecialFromDate();
              $metadata['special_to_date'] = $product->getSpecialToDate();

            }

            $data = array(
              'store_ids' => $storeIds,
              'product_id' => $product_id,
              'product_type' => $product_type,
              'title' => $product->getName(),
              'metadata' => $metadata,
              'categories' => $this->categories,
              'qty' => $product->getExtensionAttributes()->getStockItem()->getQty(),
              'visibility' => $product->getVisibility(),
              'isInStock' => $product->isInStock(),
              'createdAt' => $product->getCreatedAt(),
              'updatedAt' => $product->getUpdatedAt(),
              'status' => $product->getStatus(),
              'image_url_path' => $product->getImage(),
              'image_thumbnail_path' => $product->getThumbnail()
            );

            if ($product_type == 'simple') {
              $parent_product = $product->getTypeInstance()->getParentIdsByChild($product_id);
              $data['parent_product'] = $parent_product;
            } elseif ($product_type == 'configurable') {
              $get_used_products = $product->getTypeInstance()->getUsedProducts($product);
              foreach ($get_used_products as $simple_prod) {
                $this->_klaviyoLogger->log('configurable product saved');
                $simple_id = $simple_prod->getId();
                $this->_klaviyoLogger->log(sprintf('Simple product: %s %s', $simple_id, json_encode($simple_prod)));
              }
            }


              $this->_webhookHelper->makeWebhookRequest('product/save', $data, $klaviyoId);
            }
        }
    }
}
