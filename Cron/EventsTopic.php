<?php

namespace Klaviyo\Reclaim\Cron;

use Klaviyo\Reclaim\Helper\CategoryMapper;
use Klaviyo\Reclaim\Helper\Logger;
use Klaviyo\Reclaim\Model\SyncsFactory;
use Klaviyo\Reclaim\Model\Quote\QuoteIdMask;
use Klaviyo\Reclaim\Model\ResourceModel\Events\CollectionFactory;

use Magento\Catalog\Model\CategoryFactory;

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
     * Klaviyo helper for mapping category ids to names
     * @var CategoryMapper $categoryMapperactory
     */
    protected $_categoryMapper;
    /**
     * @param Logger $klaviyoLogger
     * @param CollectionFactory $eventsCollectionFactory
     * @param SyncsFactory $klSyncFactory
     * @param QuoteIdMask $quoteIdMaskResource
     * @param CategoryFactory $categoryFactory
     * @param CategoryMapper $categoryMapper
     */
    public function __construct(
        Logger $klaviyoLogger,
        CollectionFactory $eventsCollectionFactory,
        SyncsFactory $klSyncFactory,
        QuoteIdMask $quoteIdMaskResource,
        CategoryMapper $categoryMapper
    )
    {
        $this->_klaviyoLogger = $klaviyoLogger;
        $this->_eventsCollectionFactory = $eventsCollectionFactory;
        $this->_klSyncFactory = $klSyncFactory;
        $this->_quoteIdMaskResource = $quoteIdMaskResource;
        $this->_categoryMapper = $categoryMapper;
    }

    /**
     * Gets 500 rows from the kl_events table and move to the kl_sync table
     */
    public function moveRowsToSync()
    {
        // New Events to be moved to kl_sync table and update status of these to Moved, limit 500
        $eventsCollection = $this->_eventsCollectionFactory->create();
        $eventsData = $eventsCollection->getRowsForSync('NEW')
            ->addFieldToSelect(['id','event','payload','user_properties'])
            ->getData();

        if (empty($eventsData)){
            return;
        }

        $idsMoved = [];

        // Capture all events that have been moved and add data to Sync table
        foreach ($eventsData as $event){
            if ($event['event'] == 'Added To Cart') {
                $event['payload'] = json_encode($this->_categoryMapper->replaceQuoteIdAndCategoryIds($event['payload']));
            }

            //TODO: This can probably be done as one bulk update instead of individual inserts
            $sync = $this->_klSyncFactory->create();
            $sync->setData([
                'status' => 'NEW',
                'topic' => $event['event'],
                'user_properties' => $event['user_properties'],
                'payload' => $event['payload']
            ]);
            try {
                $sync->save();
                array_push($idsMoved, $event['id']);
            } catch (\Exception $e) {
                $this->_klaviyoLogger->log(sprintf("Unable to move row: %s", $e->getMessage()));
            }
        }

        // Update Status of rows in kl_events table to Moved
        $eventsCollection->updateRowStatus($idsMoved, 'MOVED');
    }

    /**
     * Deletes rows moved to the kl_syncs table that are older than 2 days
     */
    public function deleteMovedRows()
    {
        // Delete rows that have been moved to sync table
        $eventsCollection = $this->_eventsCollectionFactory->create();
        $idsToDelete = $eventsCollection->getIdsToDelete('MOVED');

        $eventsCollection->deleteRows($idsToDelete);
    }

    /**
     * Helper function to replace QuoteId with MaskedQuoteId and CategoryIds with CategoryNames in Added To Cart payload
     * @param string $payload
     * @return array
     */
    public function replaceQuoteIdAndCategoryIds(string $payload): array
    {
        $decoded_payload = json_decode($payload, true);

        //Set MaskedQuoteId if customer is not logged in, otherwise the same identifier from Observer
        $quoteId = $decoded_payload['QuoteId'];
        $decoded_payload['MaskedQuoteId'] = strpos($quoteId, "kx_identifier_") !== false ?
            $quoteId :
            $this->_quoteIdMaskResource->getMaskedQuoteId((int)$quoteId);
        unset($decoded_payload['QuoteId']);

        // Replace CategoryIds for Added Item, Quote Items with resp. CategoryNames
        $decoded_payload = $this->_categoryMapper->replaceCategoryIdsWithNames($decoded_payload);

        return $decoded_payload;
    }
}
