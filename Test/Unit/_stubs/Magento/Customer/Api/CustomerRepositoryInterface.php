<?php

namespace Magento\Customer\Api;

if (!interface_exists(\Magento\Customer\Api\CustomerRepositoryInterface::class, false)) {
    interface CustomerRepositoryInterface
    {
        public function save(\Magento\Customer\Api\Data\CustomerInterface $customer, $passwordHash = null);

        public function get($email, $websiteId = null);

        public function getById($customerId);

        public function delete(\Magento\Customer\Api\Data\CustomerInterface $customer);

        public function deleteById($customerId);
    }
}
