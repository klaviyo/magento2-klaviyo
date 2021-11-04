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
        $groupedRows = $this->getGroupedRows($syncCollection->getRowsForSync('NEW')->getData());

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
            $this->_klaviyoLogger->log("WARNING: kl_sync table size greater than 10000, currently sitting at $klSyncTableSize");
        }
    }

    /**
     * Retry Cron job retries all rows marked for retry in kl_sync table
     * @throws Exception
     */
    public function retry()
    {
        $syncCollection = $this->_syncCollectionFactory->create();
        $groupedRows = $this->getGroupedRows($syncCollection->getRowsForSync('RETRY')->getData());

        $this->sendUpdatesToApp($groupedRows, true);
    }

    /**
     * Sends Webhook or Track API requests based on the topic of each row
     * and creates a response manifest to updates row status
     * @param array $groupedRows
     * @param bool $isRetry
     * @throws Exception
     */
    private function sendUpdatesToApp(array $groupedRows, bool $isRetry = false)
    {
        $webhookTopics = ['product/save']; //List of topics that use webhooks
        $trackApiTopics = ['Added To Cart']; //List of topics that use the Track API

        $responseManifest = ['1' => [], '0' => []];

        foreach($groupedRows as $topic => $rows){
            if (in_array($topic, $webhookTopics) && !empty($rows)) {
                foreach($rows as $row) {
                    $response = $this->_webhookHelper->makeWebhookRequest(
                        $row['topic'],
                        $row['payload'],
                        $row['klaviyo_id']
                    );
                    if (!$response) {$response = '0';}

                    array_push( $responseManifest["$response"], $row['id']);
                }
            }

            if (in_array($topic, $trackApiTopics) && !empty($rows)) {
                foreach($rows as $row) {
                    $response = $this->_dataHelper->klaviyoTrackEvent(
                        $row['topic'],
                        json_decode($row['user_properties'], true ),
                        json_decode($row['payload'], true )
                    );
                    if (!$response) {$response = '0';}

                    array_push($responseManifest["$response"], $row['id']);
                }
            }
        }
        $this->updateRowStatuses($responseManifest, $isRetry);
    }

    /**
     * Update statues of rows to SYNCED, RETRY and FAILED based on response and if Retry cron run
     * @param array $responseManifest
     * @param bool $isRetry
     */
    private function updateRowStatuses(array $responseManifest, bool $isRetry)
    {
        $syncCollection = $this->_syncCollectionFactory->create();
        $syncCollection->updateRowStatus($responseManifest['1'], 'SYNCED');

        $syncCollection->updateRowStatus($responseManifest['0'], $isRetry ? 'FAILED' : 'RETRY');
    }

    /**
     * Groups rows from kl_sync table based on topics
     * @param array $allRows
     * @return array
     */
    private function getGroupedRows(array $allRows)
    {
        $groupedRows = [];
        foreach ($allRows as $row)
        {
            if (array_key_exists($row['topic'], $groupedRows)) {
                array_push($groupedRows[$row['topic']], $row);
            } else {
                $groupedRows[$row['topic']] = [$row];
            }
        }

        return $groupedRows;
    }
}
