<?php

namespace Magento\Customer\CustomerData;

if (!class_exists(\Magento\Customer\CustomerData\Customer::class, false)) {
    class Customer
    {
        public function getSectionData()
        {
            return [];
        }
    }
}
