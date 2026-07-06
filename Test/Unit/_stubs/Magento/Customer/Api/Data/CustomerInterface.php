<?php

namespace Magento\Customer\Api\Data;

if (!interface_exists(\Magento\Customer\Api\Data\CustomerInterface::class, false)) {
    interface CustomerInterface
    {
        public function getId();

        public function setId($id);

        public function getEmail();

        public function setEmail($email);

        public function getFirstname();

        public function setFirstname($firstname);

        public function getLastname();

        public function setLastname($lastname);
    }
}
