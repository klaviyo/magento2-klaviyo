<?php

namespace Magento\Newsletter\Model;

if (!class_exists(\Magento\Newsletter\Model\Subscriber::class, false)) {
    class Subscriber
    {
        public const STATUS_SUBSCRIBED = 1;
        public const STATUS_NOT_ACTIVE = 2;
        public const STATUS_UNSUBSCRIBED = 3;
        public const STATUS_UNCONFIRMED = 4;

        public function getId()
        {
            return null;
        }

        public function getStatus()
        {
            return null;
        }

        public function isStatusChanged()
        {
            return false;
        }

        public function isObjectNew($flag = null)
        {
            return false;
        }

        public function getCustomerId()
        {
            return null;
        }

        public function getStoreId()
        {
            return null;
        }

        public function getEmail()
        {
            return null;
        }

        public function getFirstname()
        {
            return null;
        }

        public function getLastname()
        {
            return null;
        }
    }
}
