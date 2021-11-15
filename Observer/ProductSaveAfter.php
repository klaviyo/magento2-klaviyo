<?php

namespace Klaviyo\Reclaim\Observer;

use Exception;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Model\ProductsFactory;

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
     * Klaviyo product factory
     * @var klProductFactory
     */
    protected $_klProductFactory;

    /**
     * Magento stock registry api interface
     * @var $stockRegistry
     */
    protected $_stockRegistry;

    /**
     * @param ScopeSetting $klaviyoScopeSetting
     * @param CategoryFactory $categoryFactory
     * @param ProductsFactory $klProductFactory
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        ScopeSetting $klaviyoScopeSetting,
        ProductsFactory $klProductFactory,
        StockRegistryInterface $stockRegistry
    ) {
        $this->_klaviyoScopeSetting = $klaviyoScopeSetting;
        $this->_klProductFactory = $klProductFactory;
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
            if (empty($storeIds)) {continue;}

            if ($this->_klaviyoScopeSetting->getWebhookSecret() && $this->_klaviyoScopeSetting->getProductSaveWebhookSetting($storeIds[0])) {
              $normalizedProduct = $this->normalizeProduct($product);
              $data = [
                'status'=>'NEW',
                'topic'=>'product/save',
                'klaviyo_id'=>$klaviyoId,
                'payload'=>json_encode($normalizedProduct)
              ];
              $klProduct = $this->_klProductFactory->create();
              $klProduct->setData($data);
              $klProduct->save();
            }
        }
    }

    private function normalizeProduct($product=null)
    {
      if ($product == null) {return;}

      $product_id = $product->getId();

      $product_info = array(
        'store_ids' => $product->getStoreIds(),
        'product' => array(
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
          'categories' => $product->getCategoryIds()
        )
      );

      if ($product->getSpecialPrice()) {
        $product_info['metadata']['special_price'] = $product->getSpecialPrice();
        $product_info['metadata']['special_from_date'] = $product->getSpecialFromDate();
        $product_info['metadata']['special_to_date'] = $product->getSpecialToDate();
      }
      return $product_info;
    }
}
