<?php

namespace Klaviyo\Reclaim\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Klaviyo Syncs Model is used to represent the kl_syncs table created using db_schema.xml file using declarative schema.
 * https://devdocs.magento.com/guides/v2.4/extension-dev-guide/declarative-schema/
 * The Model doesn't have the capability to query the DB directly but is required by the ResourceModel to create Magento DataObject instances
 *
 * The kl_syncs table will be read from by cron jobs to send product/order updates and track events to the Klaviyo app.
 * This table is updated by each individual topic syncs, and rows are added to this table to sync to the app using the
 * klaviyo_webhook/event_sync cron job which runs every 5 minutes
 */
class Syncs extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Klaviyo\Reclaim\Model\ResourceModel\Syncs');
    }
}
