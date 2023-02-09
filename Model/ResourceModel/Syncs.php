<?php

namespace Klaviyo\Reclaim\Model\ResourceModel;

use Klaviyo\Reclaim\Setup\SchemaInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Klaviyo Syncs Table ResourceModel.
 *
 * The ResourceModel for any Model has the ability to query and make transactions with its associated table in the database.
 * This queries through the Zend\Db adapter which is connected to the Database using the etc/env.php file.
 * The ResourceModel requires the Model to create DataObject instances and requires the table name and its idFieldName
 * to be defined.
 * https://devdocs.magento.com/guides/v2.4/architecture/archi_perspectives/persist_layer.html
 */
class Syncs extends AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init(SchemaInterface::KL_SYNC_TABLE, 'id');
    }
}
