<?php

namespace Magento\Catalog\Api;

if (!interface_exists(\Magento\Catalog\Api\ProductRepositoryInterface::class, false)) {
    interface ProductRepositoryInterface
    {
        public function get($sku, $editMode = false, $storeId = null, $forceReload = false);

        public function getById($productId, $editMode = false, $storeId = null, $forceReload = false);
    }
}
