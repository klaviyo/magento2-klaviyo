<?php

namespace Klaviyo\Reclaim\Cron;

use Klaviyo\Reclaim\Helper\Logger;

use Klaviyo\Reclaim\Model\SyncsFactory;
use Klaviyo\Reclaim\Model\Resourcemodel\Events;
use Klaviyo\Reclaim\Model\Resourcemodel\Events\CollectionFactory;

class EventsTopic
{
    /**
     * Klaviyo Logger
     * @var Logger
     */
    protected $_klaviyoLogger;

    /**
     * Klaviyo Events ResourceModel
     * @var Events
     */
    protected $_eventsResource;

    /**
     * Klaviyo Events Collection Factory
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
     * @param Events $eventsresource
     * @param CollectionFactory $eventsCollectionFactory
     * @param SyncsFactory $klSyncFactory
     */
    public function __construct(
        Logger $klaviyoLogger,
        Events $eventsresource,
        CollectionFactory $eventsCollectionFactory,
        SyncsFactory $klSyncFactory
    )
    {
        $this->_klaviyoLogger = $klaviyoLogger;
        $this->_eventsResource = $eventsresource;
        $this->_eventsCollectionFactory = $eventsCollectionFactory;
        $this->_klSyncFactory = $klSyncFactory;
    }

    /**
     *
     */
    public function moveRowsToSync()
    {
        $this->_klaviyoLogger->log('Events Topic sync running: Moving rows to Synced Table');

        // New Events to be moved to kl_sync table and update status of these to Moved, limit 500
        $events = $this->_eventsCollectionFactory->create();
        $eventsData = $events->getEventsToUpdate()->getData();

        if (empty( $eventsData )){
            $this->_klaviyoLogger->log('Events Topic sync running: No events to move, returning');
            $this->_klaviyoLogger->log('Events Topic sync aborted');
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
                'payload' => $event['payload']
            ]);
            $sync->save();

            array_push( $idsMoved, $event['id'] );
        }

        // Update Status of rows in kl_events table to Moved
        $this->_eventsResource->updateRowsToMoved($idsMoved);
        $this->_klaviyoLogger->log('Event Topic sync complete: Rows moved to Sync table and status updated in Topic table');
    }

    public function deleteMovedRows()
    {
        // Delete rows that have been moved to sync table
        $this->_klaviyoLogger->log('Event Cleanup Cron running: Deleting all rows that have been moved to Sync table');
        $idsToDelete = $this->_eventsCollectionFactory->create()->getIdsToDelete();

        $this->_eventsResource->deleteMovedRows($idsToDelete);
        $this->_klaviyoLogger->log('Event Cleanup Cron complete: Deleted all rows moved to Sync table');
    }


}
