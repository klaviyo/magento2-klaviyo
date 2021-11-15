<?php

namespace Klaviyo\Reclaim\Cron;

use Klaviyo\Reclaim\Helper\Logger;
use Klaviyo\Reclaim\Model\SyncsFactory;
use Klaviyo\Reclaim\Model\ResourceModel\Products;
use Klaviyo\Reclaim\Model\ResourceModel\Products\CollectionFactory;

use Magento\Catalog\Model\CategoryFactory;

class ProductsTopic
{
    /**
     * Klaviyo Logger
     * @var Logger
     */
    protected $_klaviyoLogger;

    /**
     * Magento product category helper
     * @var CategoryFactory $categoryFactory
     */
    protected $_categoryFactory;

    /**
     * Klaviyo Products Resource Model
     * @var Products
     */
    protected $_klProduct;

    /**
     * Klaviyo Products Collection
     * @var CollectionFactory
     */
    protected $_klProductCollectionFactory;

    /**
     * Klaviyo Syncs Model
     * @var SyncsFactory
     */
    protected $_klSyncFactory;

    /**
     * @param Logger $klaviyoLogger
     * @param Products $klProduct
     * @param SyncsFactory $klSyncFactory
     * @param CollectionFactory $klProductCollectionFactory
     */
    public function __construct(
        Logger $klaviyoLogger,
        Products $klProduct,
        CategoryFactory $categoryFactory,
        SyncsFactory $klSyncFactory,
        CollectionFactory $klProductCollectionFactory
    )
    {
        $this->_klaviyoLogger = $klaviyoLogger;
        $this->_klProduct = $klProduct;
        $this->_categoryFactory = $categoryFactory;
        $this->_klSyncFactory = $klSyncFactory;
        $this->_klProductCollectionFactory = $klProductCollectionFactory;
    }

    public function queueKlProductsForSync()
    {
        $klProductsCollection = $this->_klProductCollectionFactory->create();
        $klProductsToSync = $klProductsCollection->getRowsForSync('NEW')
            ->addFieldToSelect(['id','payload','status','topic', 'klaviyo_id'])
            ->getData();

        if (empty($klProductsToSync)) {return;}

        $idsToUpdate = [];

        foreach ($klProductsToSync as $klProductToSync)
        {
            $klProductToSync['payload'] = json_encode($this->addCategoryNames($klProductToSync['payload']));
            $klSync = $this->_klSyncFactory->create();
            $klSync->setData([
                'payload'=> $klProductToSync['payload'],
                'topic'=> $klProductToSync['topic'],
                'klaviyo_id'=>$klProductToSync['klaviyo_id'],
                'status'=> 'NEW'
            ]);
            try {
                $klSync->save();
                array_push($idsToUpdate, $klProductToSync['id']);
            } catch (\Exception $e) {
                $this->_klaviyoLogger->log(sprintf('Unable to move row: %s', $e->getMessage()));
            }
        }

        $klProductsCollection->updateRowStatus($idsToUpdate, 'MOVED');
    }

    public function clean()
    {
        $klProductsCollection = $this->_klProductCollectionFactory->create();
        $idsToDelete = $klProductsCollection->getIdsToDelete('MOVED');

        $klProductsCollection->deleteRows($idsToDelete);
    }

    /**
     * Helper function to associate category names with their ids
     * @param string $payload
     * @return array
     */
    public function addCategoryNames(string $payload): array
    {
      $decoded_payload = json_decode($payload, true);
      $category_ids = $decoded_payload['product']['categories'];
      if (empty($category_ids)) {return $decoded_payload;}
      $decoded_payload['product']['categories'] = [];
      $category_factory = $this->_categoryFactory->create();
      foreach ($category_ids as $category_id) {
        $category = $category_factory->load($category_id);
        $decoded_payload['product']['categories'][$category_id] = $category->getName();
      }
      return $decoded_payload;
    }
}
