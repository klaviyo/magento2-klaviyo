<?php

namespace Klaviyo\Reclaim\Cron;

// use Magento\Framework\App\ResourceConnection;

use Klaviyo\Reclaim\Helper\Logger;
use Klaviyo\Reclaim\Helper\Webhook;

use Klaviyo\Reclaim\Model\ResourceModel\KlSync;
use Klaviyo\Reclaim\Model\ResourceModel\KlSync\CollectionFactory;

class KlSyncs
{
    /**
     * Klaviyo Logger
     * @var Logger
     */
    protected $_klaviyoLogger;

    /**
     * Klaviyo Sync ResourceModel
     * @var KlSync
     */
    protected $_klSync;

    /**
     * KlSync Collection Factory
     * @var CollectionFactory
     */
    protected $_klSyncCollectionFactory;

    protected $_webhookHelper;

    /**
     *
     * @param
     * @param Webhook $webhookHelper
     */
    public function __construct(
        Logger $klaviyoLogger,
        KlSync $klSync,
        CollectionFactory $_klSyncCollectionFactory,
        Webhook $webhookHelper
    )
    {
        $this->_klaviyoLogger = $klaviyoLogger;
        $this->_klSync = $klSync;
        $this->_klSyncCollectionFactory = $_klSyncCollectionFactory;
        $this->_webhookHelper = $webhookHelper;
    }

    /**
     * Cron job for sending batches to Klaviyo
     *
     * @return array
     */
     public function sync()
     {
       $klSyncCollection = $this->_klSyncCollectionFactory->create();
       $klSyncs = $klSyncCollection->getRowsForSync()->getData();

       $groupedRows = [];

       foreach ( $klSyncs as $row )
       {
         if ( array_key_exists($row["topic"], $groupedRows) )
         {
           array_push($groupedRows[$row["topic"]], $row);
         }
         else
         {
           $groupedRows[$row["topic"]] = [$row];
         }
       }

       if (empty($groupedRows["product/save"]))
       {
          $this->_klaviyoLogger->log("No Klaviyo products to sync");
       }
       else
       {
         $productUpdateResponses = $this->sendProductUpdates($groupedRows["product/save"]);

         $this->_klSync->updateRowsToSynced($productUpdateResponses["successes"]);
         $this->_klSync->updateRowsToRetry($productUpdateResponses["failures"]);
       }

       return "result";
     }

     public function retry()
     {
       $klSyncCollection = $this->_klSyncCollectionFactory->create();
       $klSyncs = $klSyncCollection->getRowsForRetrySync()->getData();

       $groupedRows = [];

       foreach ( $klSyncs as $row )
       {
         if ( array_key_exists($row["topic"], $groupedRows) )
         {
           array_push($groupedRows[$row["topic"]], $row);
         }
         else
         {
           $groupedRows[$row["topic"]] = [$row];
         }
       }

       if (empty($groupedRows["product/save"]))
       {
          $this->_klaviyoLogger->log("No Klaviyo product syncs to retry");
       }
       else
       {
         $productUpdateResponses = $this->sendProductUpdates($groupedRows["product/save"]);

         $this->_klSync->updateRowsToSynced($productUpdateResponses["successes"]);
         $this->_klSync->updateRowsToFailed($productUpdateResponses["failures"]);
       }

       return;
     }

   private function sendProductUpdates($products)
   {
     $responseManifest = ["successes" => [], "failures" => []];
     foreach ($products as $product)
     {
       $response = $this->_webhookHelper->makeWebhookRequest($product["topic"], $product["payload"], $product["klaviyo_id"]);

       if ($response) {
         array_push($responseManifest["successes"], $product["id"]);
       } else {
         array_push($responseManifest["failures"], $product["id"]);
       }
     }
     return $responseManifest;
   }

     public function clean()
     {
       return;
     }
}
