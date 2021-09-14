<?php

namespace Klaviyo\Reclaim\Cron;

use Klaviyo\Reclaim\Helper\Logger;
use Klaviyo\Reclaim\Helper\Webhook;
use Klaviyo\Reclaim\Helper\Data;
use Klaviyo\Reclaim\Model\ResourceModel\Syncs;
use Klaviyo\Reclaim\Model\ResourceModel\Syncs\CollectionFactory;

class klSyncs
{
    /**
     * Klaviyo Logger
     * @var Logger
     */
    protected $_klaviyoLogger;

    /**
     * Klaviyo Sync ResourceModel
     * @var Syncs
     */
    protected $_syncResource;

    /**
     * Products Collection Factory
     * @var CollectionFactory
     */
    protected $_syncCollectionFactory;

    /**
     * Klaviyo Webhook helper
     * @var Webhook
     */
    protected $_webhookHelper;

    /**
     * Klaviyo Data Helper
     * @var Data
     */
    protected $_dataHelper;

    /**
     * @param Logger $klaviyoLogger
     * @param Syncs $syncResource
     * @param CollectionFactory $syncCollectionFactory
     * @param Data $datahelper
     * @param Webhook $webhookHelper
     */
    public function __construct(
        Logger $klaviyoLogger,
        Syncs $syncResource,
        CollectionFactory $syncCollectionFactory,
        Data $datahelper,
        Webhook $webhookHelper
    )
    {
        $this->_klaviyoLogger = $klaviyoLogger;
        $this->_syncResource = $syncResource;
        $this->_syncCollectionFactory = $syncCollectionFactory;
        $this->_dataHelper = $datahelper;
        $this->_webhookHelper = $webhookHelper;
    }

    /**
     * Sync Cron job sending webhooks and events to Klaviyo
     */
     public function sync()
     {
         $this->_klaviyoLogger->log('Klaviyo main sync running');
         $syncCollection = $this->_syncCollectionFactory->create();

         $groupedRows = $this->getGroupedRows( $syncCollection->getRowsForSync()->getData() );

         $this->sendProductUpdates($groupedRows);
         $this->sendEvents($groupedRows);

         $this->_klaviyoLogger->log('Klaviyo Main Sync complete');
     }

    /**
     * Cleanup Cron job removing rows marked as SYNCED from kl_sync table
     */
     public function clean()
     {
         $this->_klaviyoLogger->log('Klaviyo Clean Sync Table Cron Running');

         $idsToDelete = $this->_syncResource->getIdsToDelete();

         $this->_klaviyoLogger->log('Row Ids with status FAILED');
         $this->_klaviyoLogger->log( print_r( $idsToDelete, true ) );
         $this->_syncResource->deleteFailedRows($idsToDelete);

         $klSyncTableSize = $this->_syncCollectionFactory->count();
         if ($klSyncTableSize > 10000 ){
             $this->_klaviyoLogger->log("Klaviyo Clean Sync: kl_sync table size greater than 10000, currently sitting at $klSyncTableSize");
         }

         $this->_klaviyoLogger->log('Klaviyo Clean Sync Table Cron Complete');

         return;
     }

    /**
     * Retry Cron job retries all rows marked for retry in kl_sync table
     */
     public function retry()
     {
         $this->_klaviyoLogger->log("Klaviyo retry sync running");
         $syncCollection = $this->_syncCollectionFactory->create();
         $groupedRows = $this->getGroupedRows( $syncCollection->getRowsForRetrySync()->getData() );

         $this->sendProductUpdates( $groupedRows["product/save"], true );
         $this->sendEvents( $groupedRows['Added To Cart'], true );

         return;
     }

     private function sendProductUpdates($rowsToSync, $isRetry = false)
     {
         $this->_klaviyoLogger->log(print_r("sendProductUpdates invoked", true));

         if( empty($rowsToSync["product/save"]) ) { return; }

         $responseManifest = ["successes" => [], "failures" => []];
         foreach ($rowsToSync["product/save"] as $product)
         {
             $retryCount = 0;
             $response = '';
             while ( $retryCount < 2 ) {
                 if ( $response != 'successess' ) {
                     if ( $retryCount == 1 ) { sleep(3); }
                     $response = $this->getResponseStatus(
                         $this->_webhookHelper->makeWebhookRequest($product["topic"], [$product["payload"]], $product["klaviyo_id"]
                         ) );
                 }
                 $retryCount+=1;
             }
             array_push( $responseManifest["$response"], $event['id']);
         }

         $this->updateRowStatuses( $responseManifest, $isRetry );
     }

     private function sendEvents($rowsToSync, $isRetry = false)
     {
         $this->_klaviyoLogger->log('sendEvents Method invoked');

         if( empty($rowsToSync['Added To Cart']) ){ return; }

         $responseManifest = ["successes" => [], "failures" => []];
         foreach ($rowsToSync['Added To Cart'] as $event)
         {
             $retryCount = 0;
             $response = '';
             while ( $retryCount < 2 ) {
                 $this->_klaviyoLogger->log("retry count $retryCount");
                 if ( $response != 'successess' ) {
                     if ( $retryCount == 1 ) { sleep(3); }
                     $response = $this->getResponseStatus( $this->_dataHelper->klaviyoTrackEvent(
                         $event['topic'],
                         json_decode( $event['user_properties'], true ),
                         json_decode( $event['payload'], true ),
                         strtotime($event['created_at'])
                     ) );
                     $this->_klaviyoLogger->log("$response");
                 }
                 $retryCount+=1;
             }
             array_push( $responseManifest["$response"], $event['id']);
         }

         $this->updateRowStatuses( $responseManifest, $isRetry );
     }

     private function updateRowStatuses( $responseManifest, $isRetry )
     {
         $this->_syncResource->updateRowsToSynced($responseManifest["successes"]);

         if ($isRetry){
             $this->_syncResource->updateRowsToFailed($responseManifest["failures"]);
         } else {
             $this->_syncResource->updateRowsToRetry($responseManifest["failures"]);
         }

     }

     private function getResponseStatus( $response )
     {
         if ($response) {
             return "successes";
         } else {
             return "failures";
         }
     }

     private function getGroupedRows( $allRows )
     {
         $groupedRows = [];
         foreach ( $allRows as $row )
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

         return $groupedRows;
     }
}
