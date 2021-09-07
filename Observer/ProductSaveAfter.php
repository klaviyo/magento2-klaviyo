<?php

namespace Klaviyo\Reclaim\Observer;

use Exception;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Helper\Webhook;
use Klaviyo\Reclaim\Helper\Logger;
use Klaviyo\Reclaim\Model\KlProductFactory;

use Magento\Catalog\Model\CategoryFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
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
    protected $_klProductFactory;
    protected $_stockRegistry;
    protected $product_category_names = [];

    /**
     * @param Webhook $webhookHelper
     * @param ScopeSetting $klaviyoScopeSetting
     * @param CategoryFactory $categoryFactory
     * @param KlProductFactory $klProductFactory
     * @param StockRegistryInterface $stockRegistry
     * @param Logger $klaviyoLogger
     */
    public function __construct(
        Webhook $webhookHelper,
        ScopeSetting $klaviyoScopeSetting,
        CategoryFactory $categoryFactory,
        KlProductFactory $klProductFactory,
        StockRegistryInterface $stockRegistry,
        Logger $klaviyoLogger
    ) {
        $this->_webhookHelper = $webhookHelper;
        $this->_klaviyoScopeSetting = $klaviyoScopeSetting;
        $this->_categoryFactory = $categoryFactory;
        $this->_klProductFactory = $klProductFactory;
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
              $data = [
                "status"=>"NEW",
                "topic"=>"product/save",
                "klaviyo_id"=>$klaviyoId,
                "payload"=>json_encode($normalizedProduct)
              ];
              $klProduct = $this->_klProductFactory->create();
              $klProduct->setData($data);
              $klProduct->save();

              // $this->_klaviyoLogger->log( print_r("Created new row?", true));

              // $this->_webhookHelper->makeWebhookRequest('product/save', $normalizedProduct, $klaviyoId);
            }
        }
    }

    private function normalizeProduct($product=null)
    {
      if ($product == null) {
        return;
      }

      $product_id = $product->getId();

      // remove thumnbail image url?

      $product_info = array(
        'product' => array(
          'store_ids' => $product->getStoreIds(),
          'ID' => $product_id,
          'TypeID' => $product->getTypeId(),
          'Name' => $product->getName(),
          'qty' => $this->_stockRegistry->getStockItem($product_id)->getQty(),
          'Visibility' => $product->getVisibility(),
          'IsInStock' => $product->isInStock(),
          'Status' => $product->getStatus(),
          'CreatedAt' => $product->getCreatedAt(),
          'UpdatedAt' => $product->getUpdatedAt(),
          'FirstImageURL' => $product->getImage(),
          'ThumbnailImageURL' => $product->getThumbnail(),
          'metadata' => array(
            'price' => $product->getPrice(),
            'sku' => $product->getSku()
          ),
          'categories' => []
        )
      );

      if ($product->getSpecialPrice()) {
        $product_info['metadata']['special_price'] = $product->getSpecialPrice();
        $product_info['metadata']['special_from_date'] = $product->getSpecialFromDate();
        $product_info['metadata']['special_to_date'] = $product->getSpecialToDate();
      }

      $product_category_ids = $product->getCategoryIds();
      $category_factory = $this->_categoryFactory->create();
      foreach ($product_category_ids as $category_id) {
        $category = $category_factory->load($category_id);
        $product_info['categories'][$category_id] = $category->getName();
      }

      return $product_info;
    }
}
