<?php
namespace Klaviyo\Reclaim\Api;
 
interface ReclaimInterface
{
    /**
     * Returns a list of stores with extended information
     *
     * @api
     * @return mixed
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
     * @param mixed $quote_id
     * @param mixed $item_id
     * @return mixed
     */
    public function product($quote_id, $item_id);

    /**
     * Returns subscribers by date filter
     *
     * @api
     * @param mixed $start
     * @param mixed $until
     * @param mixed $storeId
     * @return mixed
     */
    public function customersubscription($start, $until, $storeId=null);

    /**
     * Returns subscirbers by id
     *
     * @api
     * @param mixed $start_id
     * @param mixed $end_id
     * @param mixed $storeId
     * @return mixed
     */
    public function historicalcustomersubscription($start_id, $end_id, $storeId=null);

     /**
     * Returns product by id range
     * @api 
     * @param mixed $start_id
     * @param mixed $end_id
     * @return mixed
     */
    public function productinspector($start_id, $end_id);

}