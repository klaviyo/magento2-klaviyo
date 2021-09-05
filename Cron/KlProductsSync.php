<?php

namespace Klaviyo\Reclaim\Cron;

use Klaviyo\Reclaim\Helper\Logger;
use Klaviyo\Reclaim\Model\KlSyncFactory;
use Klaviyo\Reclaim\Model\ResourceModel\KlProduct;
use Klaviyo\Reclaim\Model\ResourceModel\KlProduct\CollectionFactory;

class KlProductsSync
{
    protected $_klaviyoLogger;
    protected $_klProduct;
    protected $_klProductCollectionFactory;
    protected $_klSyncFactory;

    public function __construct(
        Logger $klaviyoLogger,
        KlProduct $klProduct,
        KlSyncFactory $klSyncFactory,
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
       // if ($this->isCronStillRunning("klaviyo_queue_products_for_sync"))
       // {
       //   $this->_klaviyoLogger->log("Skipping queueKlProductsSync, previous job still running");
       // }

       $this->_klaviyoLogger->log("queueKlProductsForSync running");
       $klProductsCollection = $this->_klProductCollectionFactory->create();
       $klProductsToSync = $klProductsCollection->getKlProductsToQueueForSync()->getData();

       if (empty($klProductsToSync))
       {
         $this->_klaviyoLogger->log("No products to queue.");
         return;
       }

       $idsToDelete = [];

       foreach ($klProductsToSync as $klProductToSync)
       {
         $klSync = $this->_klSyncFactory->create();
         $klSync->setData([
           "payload"=> $klProductToSync["payload"],
           "topic"=> $klProductToSync["topic"],
           "status"=> "New"
         ]);
         $klSync->save();
         array_push($idsToDelete, $klProductToSync["id"]);
       }

       $this->_klaviyoLogger->log("Ids that need to be deleted below");
       $this->_klaviyoLogger->log( print_r( $idsToDelete, true) );

       $this->_klProduct->deleteMovedRows($idsToDelete);
       $this->_klaviyoLogger->log("queueKlProductsForSync done");
     }

    //  public function isCronStillRunning($cronJobName)
    //  {
    //   $currentRunningJob = $this->cronCollection->create()
    //       ->addFieldToFilter('job_code', $cronJobName)
    //       ->addFieldToFilter('status', 'running')
    //      	->setPageSize(1);
    //
    //   if ($currentRunningJob->getSize()) {
    //      	$sameJobAlreadyRunning = $this->cronCollection->create()
    //              ->addFieldToFilter('job_code', $jobCode)
    //              ->addFieldToFilter('scheduled_at', $currentRunningJob->getFirstItem()->getScheduledAt())
    //              ->addFieldToFilter('status', ['in' => ['success', 'failed']]);
    //
    //       return ($sameJobAlreadyRunning->getSize()) ? true : false;
    //   }
    //
    //   return false;
    // }
     //
     // public function deleteMovedRows()
     // {
     //     $this->_klaviyoLogger->log("KlProduct cleanup cron running");
     //     $klProductsCollection = $this->_klProductCollectionFactory->create();
     //     $idsToDelete = $klProductsCollection->getIdsToDelete();
     //
     //     $this->_klaviyoLogger->log("ids of products to delete below");
     //     $this->_klaviyoLogger->log(print_r($idsToDelete, true));
         // $this->_klProduct->deleteMovedRows($idsToDelete);
     // }
}
