<?php

namespace Klaviyo\Reclaim\Cron;

use Exception;
use Klaviyo\Reclaim\Helper\Logger;
use Klaviyo\Reclaim\Helper\Webhook;
use Klaviyo\Reclaim\Helper\Data;
use Klaviyo\Reclaim\Model\ResourceModel\Syncs\CollectionFactory;

class KlSyncs
{
    /**
     * Klaviyo Logger
     * @var Logger
     */
    protected $_klaviyoLogger;

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
     * @param CollectionFactory $syncCollectionFactory
     * @param Data $datahelper
     * @param Webhook $webhookHelper
     */
    public function __construct(
        Logger $klaviyoLogger,
        CollectionFactory $syncCollectionFactory,
        Data $datahelper,
        Webhook $webhookHelper
    )
    {
        $this->_klaviyoLogger = $klaviyoLogger;
        $this->_syncCollectionFactory = $syncCollectionFactory;
        $this->_dataHelper = $datahelper;
        $this->_webhookHelper = $webhookHelper;
    }

    /**
     * Sync Cron job sending webhooks and events to Klaviyo
     * @throws Exception
     */
     public function sync()
     {
         $syncCollection = $this->_syncCollectionFactory->create();

         $groupedRows = $this->getGroupedRows( $syncCollection->getRowsForSync('NEW')->getData() );

//         $this->sendProductUpdates($groupedRows);
//         $this->sendEvents($groupedRows);

         $this->sendUpdatesToApp($groupedRows);

     }

    /**
     * Cleanup Cron job removing rows marked as SYNCED from kl_sync table
     */
     public function clean()
     {
         $syncCollection = $this->_syncCollectionFactory->create();
         $idsToDelete = $syncCollection->getIdsToDelete('SYNCED');

         $syncCollection->deleteRows($idsToDelete);

         $klSyncTableSize = $syncCollection->count();
         if ($klSyncTableSize > 10000 ){
             $this->_klaviyoLogger->log("Klaviyo Clean Sync: kl_sync table size greater than 10000, currently sitting at $klSyncTableSize");
         }
     }

    /**
     * Retry Cron job retries all rows marked for retry in kl_sync table
     * @throws Exception
     */
     public function retry()
     {
         $syncCollection = $this->_syncCollectionFactory->create();
         $groupedRows = $this->getGroupedRows( $syncCollection->getRowsForSync('RETRY')->getData() );

//         $this->sendProductUpdates($groupedRows, true);
//         $this->sendEvents($groupedRows, true);

         $this->sendUpdatesToApp($groupedRows, true);

         return;
     }

    /**
     * Sends Webhook or Track API requests based on the topic of each row
     * and creates a response manifest to updates row status
     * @param $groupedRows
     * @param bool $isRetry
     * @throws Exception
     */
     private function sendUpdatesToApp($groupedRows, bool $isRetry = false)
     {
         $responseManifest = ['1' => [], '0' => []];
         foreach($groupedRows as $topic => $rows){
             if ($topic == 'product/save' && !empty($rows)) {
                 foreach($rows as $row) {
                     $response = $this->_webhookHelper->makeWebhookRequest(
                         $row['topic'],
                         $row['payload'],
                         $row['klaviyo_id']
                     );

                     array_push( $responseManifest["$response"], $row['id']);
                 }
             }

             if ($topic == 'Added to Cart' && !empty($rows)) {
                 foreach($rows as $row) {
                     $response = $this->_dataHelper->klaviyoTrackEvent(
                         $row['topic'],
                         json_decode($row['user_properties'], true ),
                         json_decode($row['payload'], true ),
                         strtotime($row['created_at'])
                     );

                     array_push( $responseManifest["$response"], $row['id']);
                 }
             }
         }
         $this->updateRowStatuses( $responseManifest, $isRetry );
     }

    /**
     * Update statues of rows to SYNCED, RETRY and FAILED based on response and if Retry cron run
     * @param $responseManifest
     * @param $isRetry
     */
      private function updateRowStatuses( $responseManifest, $isRetry )
     {
         $syncCollection = $this->_syncCollectionFactory->create();
         $syncCollection->updateRowStatus($responseManifest['1'], 'SYNCED');

         if ($isRetry){
             $syncCollection->updateRowStatus($responseManifest['0'], 'FAILED');
         } else {
             $syncCollection->updateRowStatus($responseManifest['0'], 'RETRY');
         }

     }

     private function getGroupedRows( $allRows )
     {
         $groupedRows = [];
         foreach ( $allRows as $row )
         {
             if ( array_key_exists($row['topic'], $groupedRows) )
             {
                 array_push($groupedRows[$row['topic']], $row);
             }
             else
             {
                 $groupedRows[$row['topic']] = [$row];
             }
         }

         return $groupedRows;
     }

//     private function sendProductUpdates($rowsToSync, $isRetry = false)
//     {
//         if( empty($rowsToSync['product/save']) ) { return; }
//
//         $responseManifest = ['1' => [], '0' => []];
//         foreach ($rowsToSync['product/save'] as $product)
//         {
//             $response = $this->_webhookHelper->makeWebhookRequest(
//                 $product['topic'],
//                 $product['payload'],
//                 $product['klaviyo_id']
//             );
//
//             array_push( $responseManifest["$response"], $product['id']);
//         }
//
//         $this->updateRowStatuses( $responseManifest, $isRetry );
//     }
//
//     private function sendEvents($rowsToSync, $isRetry = false)
//     {
//         if( empty($rowsToSync['Added To Cart']) ){ return; }
//
//         $responseManifest = ['1' => [], '0' => []];
//         foreach ($rowsToSync['Added To Cart'] as $event)
//         {
//             $response = $this->_dataHelper->klaviyoTrackEvent(
//                 $event['topic'],
//                 json_decode( $event['user_properties'], true ),
//                 json_decode( $event['payload'], true ),
//                 strtotime($event['created_at'])
//             );
//
//             array_push( $responseManifest["$response"], $event['id']);
//         }
//
//         $this->updateRowStatuses( $responseManifest, $isRetry );
//     }
}
