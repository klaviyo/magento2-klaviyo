<?php

namespace Magento\Customer\Model;

if (!class_exists(\Magento\Customer\Model\CustomerFactory::class, false)) {
    class CustomerFactory
    {
        public function create(): Customer
        {
            return new Customer();
        }
    }
}
