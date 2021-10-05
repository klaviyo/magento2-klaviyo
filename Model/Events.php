<?php

namespace Klaviyo\Reclaim\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Klaviyo Events Model is used to represent the kl_events table created using db_schema.xml file using declarative schema.
 * https://devdocs.magento.com/guides/v2.4/extension-dev-guide/declarative-schema/
 * The Model doesn't have the capability to query the DB directly but is required by the ResourceModel to create Magento DataObject instances
 *
 * The kl_events table will be used as an intermediary between the Observers and kl_syncs table
 * This table records all user activity events to be sent over to the Klaviyo app using Track API
 * eg. Added to Cart
 */
class Events extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Klaviyo\Reclaim\Model\ResourceModel\Events');
    }
}
