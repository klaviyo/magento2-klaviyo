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
    ) {
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
     * Cleanup Cron job removing rows marked as SYNCED or FAILED from kl_sync table
     * FAILED syncs should be removed so they don't
     */
    public function clean()
    {
        $statusesToClean = ['SYNCED', 'FAILED'];
        $syncCollection = $this->_syncCollectionFactory->create();
        $idsToDelete = $syncCollection->getIdsToDelete($statusesToClean);

        $syncCollection->deleteRows($idsToDelete);

        $klSyncTableSize = $syncCollection->count();
        if ($klSyncTableSize > 10000) {
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

        $responseManifest = ['success' => [], 'failure' => []];
        $failedRows = [];
        $syncedRows = [];

        foreach ($groupedRows as $topic => $rows) {
            if (in_array($topic, $webhookTopics) && !empty($rows)) {
                // This block is not currently used
                foreach ($rows as $row) {
                    $response = $this->_webhookHelper->makeWebhookRequest(
                        $row['topic'],
                        $row['payload'],
                        $row['klaviyo_id']
                    );
                    if (!$response) {
                        $response = '0';
                    }

                    array_push($responseManifest["$response"], $row['id']);
                }
            }

            if (in_array($topic, $trackApiTopics) && !empty($rows)) {
                foreach ($rows as $row) {
                    try {
                        $decodedPayload = json_decode($row['payload'], true);

                        if (is_null($decodedPayload)) {
                            // payload was likely truncated, default to failed response value for the row
                            $this->_klaviyoLogger->log(sprintf("[sendUpdatesToApp] Truncated Payload - Unable to process and sync row %d", $row['id']));
                            $failedRows[] = $row['id'];
                            continue;
                        }

                        $eventTime = $decodedPayload['time'];
                        unset($decodedPayload['time']);

                        //TODO: if conditional for backward compatibility, needs to be removed in future versions
                        $storeId = '';
                        if (isset($decodedPayload['StoreId'])) {
                            $storeId = $decodedPayload['StoreId'];
                            unset($decodedPayload['StoreId']);
                        }

                        $response = $this->_dataHelper->klaviyoTrackEvent(
                            $row['topic'],
                            json_decode($row['user_properties'], true),
                            $decodedPayload,
                            $eventTime,
                            $storeId
                        );

                        if (isset($response['errors'])) {
                            $failedRows[] = $row['id'];
                        } else {
                            $syncedRows[] = $row['id'];
                        }
                    } catch (\Exception $e) {
                        // Catch an exception raised while processing or sending the event
                        // defaults to a failed response and allows the other rows to continue syncing
                        $this->_klaviyoLogger->log(sprintf("[sendUpdatesToApp] Unable to process and sync row %d: %s", $row['id'], $e->getMessage()));
                        $failedRows[] = $row['id'];
                        continue;
                    }
                }
            }
        }
        $this->updateRowStatuses($syncedRows, $failedRows, $isRetry);
    }

    /**
     * Update statues of rows to SYNCED, RETRY and FAILED based on response and if Retry cron run
     * @param array $syncedRows
     * @param array $failedRows
     * @param bool $isRetry
     */
    private function updateRowStatuses(array $syncedRows, array $failedRows, bool $isRetry)
    {
        $syncCollection = $this->_syncCollectionFactory->create();
        $syncCollection->updateRowStatus($syncedRows, 'SYNCED');

        $syncCollection->updateRowStatus($failedRows, $isRetry ? 'FAILED' : 'RETRY');
    }

    /**
     * Groups rows from kl_sync table based on topics
     * @param array $allRows
     * @return array
     */
    private function getGroupedRows(array $allRows)
    {
        $groupedRows = [];
        foreach ($allRows as $row) {
            if (array_key_exists($row['topic'], $groupedRows)) {
                array_push($groupedRows[$row['topic']], $row);
            } else {
                $groupedRows[$row['topic']] = [$row];
            }
        }

        return $groupedRows;
    }
}
