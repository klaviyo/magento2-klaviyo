<?php

namespace Magento\Catalog\Model;

if (!class_exists(\Magento\Catalog\Model\Product::class, false)) {
    class Product
    {
        public function getId()
        {
            return null;
        }

        public function getName()
        {
            return null;
        }

        public function getSku()
        {
            return null;
        }

        public function getProductUrl()
        {
            return null;
        }

        public function getTypeId()
        {
            return null;
        }

        public function getPrice()
        {
            return null;
        }

        public function getPriceInfo()
        {
            return null;
        }

        public function getCategoryCollection()
        {
            return null;
        }

        public function getCategoryIds()
        {
            return [];
        }

        public function getTypeInstance()
        {
            return null;
        }

        public function setStoreId($storeId)
        {
            return $this;
        }

        public function load($productId)
        {
            return $this;
        }
    }
}
