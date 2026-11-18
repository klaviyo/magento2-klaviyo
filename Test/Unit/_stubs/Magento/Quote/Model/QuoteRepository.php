<?php

namespace Magento\Quote\Model;

if (!class_exists(\Magento\Quote\Model\QuoteRepository::class, false)) {
    class QuoteRepository
    {
        public function getActiveForCustomer($customerId)
        {
            return null;
        }

        public function getActive($cartId)
        {
            return null;
        }
    }
}
