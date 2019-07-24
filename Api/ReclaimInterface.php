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
    public function productVariantInventory($product_id, $store_id=0);

    /**
     * Returns product by id range
     *
     * @api
     * @param int $quote_id
     * @param int $item_id
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
    public function getSubscribersByDateRange($start, $until, $store_id=null);

    /**
     * Returns subscirbers by id
     *
     * @api
     * @param string $start_id
     * @param string $end_id
     * @param string $store_id
     * @return mixed[]
     */
    public function getSubscribersById($start_id, $end_id, $store_id=null);

}