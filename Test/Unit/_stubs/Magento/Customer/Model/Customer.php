<?php

namespace Magento\Customer\Model;

if (!class_exists(\Magento\Customer\Model\Customer::class, false)) {
    class Customer
    {
        public function getData(): array
        {
            return [];
        }
        public function load($id): self
        {
            return $this;
        }
        public function getDefaultShippingAddress()
        {
            return false;
        }
    }
}
