<?php

namespace Magento\Catalog\Model\ResourceModel\Category;

if (!class_exists(\Magento\Catalog\Model\ResourceModel\Category\Collection::class, false)) {
    class Collection
    {
        public function addAttributeToSelect($attribute)
        {
            return $this;
        }

        public function getColumnValues($column)
        {
            return [];
        }

        public function load($id = null)
        {
            return $this;
        }
    }
}
