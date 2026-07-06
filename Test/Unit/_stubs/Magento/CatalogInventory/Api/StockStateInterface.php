<?php

namespace Magento\CatalogInventory\Api;

if (!interface_exists(\Magento\CatalogInventory\Api\StockStateInterface::class, false)) {
    interface StockStateInterface
    {
        public function verifyStock($productId, $scopeId = null);

        public function verifyNotification($productId, $scopeId = null);

        public function checkQuoteItemQty($productId, $itemQty, $qtyToCheck, $origQty, $scopeId = null);

        public function checkQty($productId, $qty, $scopeId = null);

        public function suggestQty($productId, $qty, $scopeId = null);

        public function getStockQty($productId, $scopeId = null);
    }
}
