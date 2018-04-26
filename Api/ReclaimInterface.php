<?php
namespace Klaviyo\Reclaim\Api;
 
interface ReclaimInterface
{
    /**
     * Returns a list of stores with extended information
     *
     * @api
     * @return JSON
     */
    public function stores();
}
