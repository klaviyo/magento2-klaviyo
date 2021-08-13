<?php

namespace Klaviyo\Reclaim\Model;

class Cron
{
    /**
     * Cron job for queueing products into the Sync table.
     *
     * @return array
     */
     public function queueProducts()
     {
       $result = [];

       // get the amount of products that we should queue

       // add the oldest rows from the kl_products table to the $result array until its length is how many we should queue

       // add the data from the $result array to the kl_sync table

       // if it worked, delete the rows from the kl_products table

       // if it didn't work, log i guess? also delete?

       return result;
     }
}
