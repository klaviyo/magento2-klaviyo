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
     * Returns greeting message to user
     *
     * @api
     * @param mixed $filter
     * @return mixed
     */
    public function product($filter);

     /**
     * @param mixed $filter
     * @return mixed
     */
    public function productinspector($filter);

}