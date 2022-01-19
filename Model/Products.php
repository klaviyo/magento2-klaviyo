<?php

namespace Klaviyo\Reclaim\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Klaviyo Products Model is used to represent the kl_products table created using db_schema.xml file using declarative schema.
 * https://devdocs.magento.com/guides/v2.4/extension-dev-guide/declarative-schema/
 * The Model doesn't have the capability to query the DB directly but is required by the ResourceModel to create Magento DataObject instances
 *
 * The kl_products table will be used as an intermediary between the Observers and kl_syncs table
 * This table records all product related updates to be sent for sync to the Klaviyo app using webhooks
 * eg. product/save webhooks
 */
class Products extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Klaviyo\Reclaim\Model\ResourceModel\Products');
    }
}
