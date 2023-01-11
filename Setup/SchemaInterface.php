<?php

namespace Klaviyo\Reclaim\Setup;

/**
 * Provides a list of all Klaviyo tables in the Magento Database.
 */
interface SchemaInterface
{
    const KL_EVENTS_TOPIC_TABLE = 'kl_events';
    const KL_PRODUCTS_TOPIC_TABLE = 'kl_products';
    const KL_SYNC_TABLE = 'kl_sync';
}
