<?php

namespace Magento\Framework\Pricing\PriceInfo;

if (!class_exists(\Magento\Framework\Pricing\PriceInfo\Base::class, false)) {
    class Base
    {
        public function getPrice($priceCode)
        {
            return null;
        }
    }
}
