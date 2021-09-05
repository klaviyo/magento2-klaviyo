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
       $this->_klaviyoLogger->log("Klaviyo main sync running");
       $klSyncCollection = $this->_klSyncCollectionFactory->create();
       $klSyncs = $klSyncCollection->getRowsForSync()->getData();

       // $this->_klaviyoLogger->log("getRowsForSync query result below");
       // $this->_klaviyoLogger->log( print_r( $klSyncs, true ) );

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

       // $this->_klaviyoLogger->log("grouped rows result below");
       // $this->_klaviyoLogger->log( print_r( $groupedRows["product/save"], true ) );

       $productUpdateResponses = $this->sendProductUpdates($groupedRows["product/save"]);
       $this->_klaviyoLogger->log( "product update responses below" );
       $this->_klaviyoLogger->log( print_r( $productUpdateResponses, true ) );


       // $idsToUpdate = array_map(function($row) { return $row["id"]; }, $klSyncs);
       // $this->_klaviyoLogger->log("Ids that need status to be changed below");
       // $this->_klaviyoLogger->log( print_r( $idsToUpdate, true) );




       // get connection to db
       // $connection = $this->_resourceConnection->getConnection();
       // name of sync table
       // $klSyncTable = $connection->getTableName("kl_sync");
       // build a query to get the 3 oldest non-processed rows
       // $selectQuery = "select id, payload, topic from $klSyncTable where status = New limit 3";
       // run the query to get the oldest rows for processing
       // $result = $connection->fetchAll($selectQuery);
       // break the result up into groups based on the webhook topic

       // send the product/save payloads to the method for sending to Klaviyo

       return "result";
     }

     private function sendProductUpdates($products)
     {
       $this->_klaviyoLogger->log(print_r("sendProductUpdates invoked", true));
       $responseManifest = ["successes" => [], "failures" => []];
       foreach ($products as $product)
       {
         $response = $this->_webhookHelper->makeWebhookRequest($product["topic"], [$product["payload"]], $product["klaviyo_id"]);
         // array_push($responseManifest["successes"], $product["id"]);
         $this->_klaviyoLogger->log(print_r("webhook response below", true));
         $this->_klaviyoLogger->log(print_r($response, true));
         $this->_klaviyoLogger->log(print_r("webhook response above", true));
         // array_push($responseManifest["failures"], $product["id"]);

       }
       return $responseManifest;
     }

     public function retry()
     {
       $this->_klaviyoLogger->log("Klaviyo retry sync running");
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

       $productUpdateResponses = $this->sendProductUpdates($groupedRows["product/save"]);
       $this->_klaviyoLogger->log( "product update responses below" );
       $this->_klaviyoLogger->log( print_r( $productUpdateResponses, true ) );

       return;
     }

     public function clean()
     {
       return;
     }
}
