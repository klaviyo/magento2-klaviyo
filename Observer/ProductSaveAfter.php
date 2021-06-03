<?php

namespace Klaviyo\Reclaim\Observer;

use Exception;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Helper\Webhook;
use Klaviyo\Reclaim\Helper\Logger;

use Magento\Catalog\Model\CategoryFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;


class ProductSaveAfter implements ObserverInterface
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
    protected $_productTypeConfigurable;
    protected $_stockRegistry;
    protected $product_category_names = [];

    /**
     * @param Webhook $webhookHelper
     * @param ScopeSetting $klaviyoScopeSetting
     * @param CategoryFactory $categoryFactory
     * @param Configurable $productTypeConfigurable
     * @param StockRegistryInterface $stockRegistry
     * @param Logger $klaviyoLogger
     */
    public function __construct(
        Webhook $webhookHelper,
        ScopeSetting $klaviyoScopeSetting,
        CategoryFactory $categoryFactory,
        Configurable $productTypeConfigurable,
        StockRegistryInterface $stockRegistry,
        Logger $klaviyoLogger
    ) {
        $this->_webhookHelper = $webhookHelper;
        $this->_klaviyoScopeSetting = $klaviyoScopeSetting;
        $this->_categoryFactory = $categoryFactory;
        $this->_productTypeConfigurable = $productTypeConfigurable;
        $this->_klaviyoLogger = $klaviyoLogger;
        $this->_stockRegistry = $stockRegistry;
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
        $storeIds = $product->getStoreIds();
        $storeIdKlaviyoMap = $this->_klaviyoScopeSetting->getStoreIdKlaviyoAccountSetMap($storeIds);

        foreach ($storeIdKlaviyoMap as $klaviyoId => $storeIds) {
            if (empty($storeIds)) {
                continue;
            }

            if ($this->_klaviyoScopeSetting->getWebhookSecret() && $this->_klaviyoScopeSetting->getProductSaveAfterSetting($storeIds[0])) {

              $normalizedProduct = $this->normalizeProduct($product);
              if ($normalizedProduct['TypeID'] == 'configurable') {
                $children = $product->getTypeInstance()->getUsedProducts($product);
                foreach ($children as $child) {
                  $normalizedChildProduct = $this->normalizeProduct($child);
                  array_push($normalizedProduct['variants'], $normalizedChildProduct);
                }
              }

                $this->_webhookHelper->makeWebhookRequest('product/save', $normalizedProduct, $klaviyoId);
            }
        }
    }

    private function normalizeProduct($product=null)
    {
      if ($product == null) {
        return;
      }

      $product_info = [];

      $product_info['store_ids'] = $product->getStoreIds();
      $product_info['ID'] = $product->getId();
      $product_info['TypeID'] = $product->getTypeId();
      $product_info['Name'] = $product->getName();
      $product_info['qty'] = $this->_stockRegistry->getStockItem($product->getId())->getQty();

      $product_info['Visibility'] = $product->getVisibility();
      $product_info['IsInStock'] = $product->isInStock();
      $product_info['Status'] = $product->getStatus();

      $product_info['CreatedAt'] = $product->getCreatedAt();
      $product_info['UpdatedAt'] = $product->getUpdatedAt();

      $product_info['FirstImageUrl'] = $product->getImage();
      $product_info['ThumbnailImageURL'] = $product->getThumbnail();

      $product_info['metadata'] = array(
        'price' => $product->getPrice(),
        'sku' => $product->getSku()
      );

      if ($product->getSpecialPrice()) {
        $product_info['metadata']['special_price'] = $product->getSpecialPrice();
        $product_info['metadata']['special_from_date'] = $product->getSpecialFromDate();
        $product_info['metadata']['special_to_date'] = $product->getSpecialToDate();
      }

      $product_info['categories'] = [];
      $product_category_ids = $product->getCategoryIds();
      $category_factory = $this->_categoryFactory->create();
      foreach ($product_category_ids as $category_id) {
        $category = $category_factory->load($category_id);
        $product_info['categories'][$category_id] = $category->getName();
      }

      $parent_product = $this->_productTypeConfigurable->getParentIdsByChild($product_info['ID']);

      if (isset($parent_product[0])) {
        $product_info['parent_product_id'] = $parent_product[0];
      } else {
        $product_info['parent_product_id'] = '';
      }

      $product_info['variants'] = [];

      return $product_info;
    }
}
