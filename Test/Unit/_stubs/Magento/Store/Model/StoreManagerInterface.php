<?php

namespace Magento\Store\Model;

if (!interface_exists(\Magento\Store\Model\StoreManagerInterface::class, false)) {
    interface StoreManagerInterface
    {
        public function setIsSingleStoreModeAllowed($value);

        public function hasSingleStore();

        public function isSingleStoreMode();

        public function getStore($storeId = null);

        public function getStores($withDefault = false, $codeKey = false);

        public function getWebsite($websiteId = null);

        public function getWebsites($withDefault = false, $codeKey = false);

        public function reinitStores();

        public function getDefaultStoreView();

        public function getGroup($groupId = null);

        public function getGroups($withDefault = false);

        public function setCurrentStore($store);
    }
}
