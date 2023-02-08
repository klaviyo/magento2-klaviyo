<?php

namespace Klaviyo\Reclaim\Api;

interface ReclaimInterface
{
    /**
     * Returns a list of stores with extended information
     *
     * @api
     * @return mixed[]
     */
    public function stores();

    /**
     * Returns a the current extension version
     *
     * @api
     * @return string
     */
    public function reclaim();

    /**
     * Returns the webhook secret
     *
     * @return string
     * @api
     */
    public function getWebhookSecret();

    /**
     * Returns the Klaviyo log file
     *
     * @api
     * @return mixed[]
     */
    public function getLog();

    /**
     * Cleans the Klaviyo log file
     *
     * @api
     * @param string $date
     * @return mixed[]
     */
    public function cleanLog($date);

    /**
     * Appends a message to the Klaviyo log file
     *
     * @api
     * @param string $message
     * @return mixed[]
     */
    public function appendLog($message);

    /**
     * Returns product information
     *
     * @api
     * @param int $quote_id
     * @param int $item_id
     * @return mixed[]
     */
    public function product($quote_id, $item_id);

    /**
     * Returns product information with its variants
     *
     * @api
     * @param int $product_id
     * @param int $store_id
     * @return mixed[]
     */
    public function productVariantInventory($product_id, $store_id = 0);

    /**
     * Returns product by id range
     *
     * @api
     * @param int $start_id
     * @param int $end_id
     * @return mixed[]
     */
    public function productinspector($start_id, $end_id);

    /**
     * Returns subscribers by date filter
     *
     * @api
     * @return mixed[]
     */

    public function getSubscribersCount();

    /**
     * Returns subscribers by date filter
     *
     * @api
     * @param string $start
     * @param string $until
     * @param int $store_id
     * @return mixed[]
     */
    public function getSubscribersByDateRange($start, $until, $store_id = null);

    /**
     * Returns subscirbers by id
     *
     * @api
     * @param string $start_id
     * @param string $end_id
     * @param int $store_id
     * @return mixed[]
     */
    public function getSubscribersById($start_id, $end_id, $store_id = null);
}
