<?php

namespace Klaviyo\Reclaim\Cron;

use Klaviyo\Reclaim\Helper\Logger;
use Klaviyo\Reclaim\Model\SyncsFactory;
use Klaviyo\Reclaim\Model\ResourceModel\Products;
use Klaviyo\Reclaim\Model\ResourceModel\Products\CollectionFactory;

class ProductsTopic
{
    /**
     * Klaviyo Logger
     * @var Logger
     */
    protected $_klaviyoLogger;

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
        SyncsFactory $klSyncFactory,
        CollectionFactory $klProductCollectionFactory
    )
    {
        $this->_klaviyoLogger = $klaviyoLogger;
        $this->_klProduct = $klProduct;
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
}
