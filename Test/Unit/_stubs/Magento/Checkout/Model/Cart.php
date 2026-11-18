<?php

namespace Magento\Checkout\Model;

if (!class_exists(\Magento\Checkout\Model\Cart::class, false)) {
    class Cart
    {
        public function getQuote()
        {
            return null;
        }
    }
}
