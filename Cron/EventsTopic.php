<?php

namespace Klaviyo\Reclaim\Cron;

use Klaviyo\Reclaim\Helper\Logger;
use Klaviyo\Reclaim\Model\SyncsFactory;
use Klaviyo\Reclaim\Model\Quote\QuoteIdMask;
use Klaviyo\Reclaim\Model\Resourcemodel\Events\CollectionFactory;

class EventsTopic
{
    /**
     * Klaviyo Logger
     * @var Logger
     */
    protected $_klaviyoLogger;

    /**
     * Klaviyo QuoteIdMask ResourceModel
     * @var QuoteIdMask
     */
    protected $_quoteIdMaskResource;

    /**
     * Klaviyo Events Collection Facd ..ctory
     * @var CollectionFactory
     */
    protected $_eventsCollectionFactory;

    /**
     * Klaviyo Sync Factory
     * @var SyncsFactory
     */
    protected $_klSyncFactory;

    /**
     * @param Logger $klaviyoLogger
     * @param CollectionFactory $eventsCollectionFactory
     * @param SyncsFactory $klSyncFactory
     * @param QuoteIdMask $quoteIdMaskResource
     */
    public function __construct(
        Logger $klaviyoLogger,
        CollectionFactory $eventsCollectionFactory,
        SyncsFactory $klSyncFactory,
        QuoteIdMask $quoteIdMaskResource
    )
    {
        $this->_klaviyoLogger = $klaviyoLogger;
        $this->_eventsCollectionFactory = $eventsCollectionFactory;
        $this->_klSyncFactory = $klSyncFactory;
        $this->_quoteIdMaskResource = $quoteIdMaskResource;
    }

    /**
     * Cron method used by klaviyo_events_topic cron job
     * Reads rows from kl_events table and writes to the kl_sync table
     */
    public function moveRowsToSync()
    {
        // New Events to be moved to kl_sync table and update status of these to Moved, limit 500
        $eventsCollection = $this->_eventsCollectionFactory->create();
        $eventsData = $eventsCollection->getRowsForSync('NEW')
            ->addFieldToSelect(['id','event','payload','user_properties'])
            ->getData();

        if (empty( $eventsData )){
            return;
        }

        $idsMoved = [];

        // Capture all events that have been moved and add data to Sync table
        foreach ( $eventsData as $event ){
            //TODO: This can probably be done as one bulk update instead of individual inserts
            $sync = $this->_klSyncFactory->create();
            $sync->setData([
                'status' => 'NEW',
                'topic' => $event['event'],
                'user_properties' => $event['user_properties'],
                'payload' => $this->addMaskedQuoteIdToEventPayload(json_decode($event['payload'], true))
            ]);
            try {
                $sync->save();
                array_push($idsMoved, $event['id']);
            } catch (\Exception $e) {
                $this->_klaviyoLogger->log(sprintf("Unable to move row: %s", $e));
            }
        }

        // Update Status of rows in kl_events table to Moved
        $eventsCollection->updateRowStatus($idsMoved, 'MOVED');
    }

    /**
     * Cron method used by the klaviyo_events_cleanup cron job
     * Delete all rows marked as MOVED and older than 2 days in the kl_events table
     */
    public function deleteMovedRows()
    {
        // Delete rows that have been moved to sync table
        $eventsCollection = $this->_eventsCollectionFactory->create();
        $idsToDelete = $eventsCollection->getIdsToDelete('MOVED');

        $eventsCollection->deleteRows($idsToDelete);
    }

    /**
     * Helper method to get MaskedQuoteId from quote_id_mask table and replace QuoteId in payload
     * This needs to be done here since MaskedQuoteId is unavailable when rows are recorded in kl_events table
     * @param $payload
     * @return false|string
     */
    public function addMaskedQuoteIdToEventPayload( $payload )
    {
        $maskedQuoteId = $this->_quoteIdMaskResource->getMaskedQuoteId(( $payload['QuoteId'] ));
        unset($payload['QuoteId']);

        return $payload = json_encode(array_merge(
            $payload,
            array('MaskedQuoteId' => $maskedQuoteId)
        ));
    }

}
