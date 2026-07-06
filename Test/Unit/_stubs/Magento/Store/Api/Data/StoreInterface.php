<?php

namespace Magento\Store\Api\Data;

if (!interface_exists(\Magento\Store\Api\Data\StoreInterface::class, false)) {
    interface StoreInterface
    {
        public function getId();

        public function setId($id);

        public function getCode();

        public function setCode($code);

        public function getName();

        public function setName($name);

        public function getWebsiteId();

        public function setWebsiteId($websiteId);

        public function getStoreGroupId();

        public function setStoreGroupId($storeGroupId);

        public function getIsActive();

        public function setIsActive($isActive);
    }
}
