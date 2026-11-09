<?php

namespace Magento\Framework\Model;

require_once __DIR__ . '/../DataObject.php';

if (!class_exists(\Magento\Framework\Model\AbstractModel::class, false)) {
    class AbstractModel extends \Magento\Framework\DataObject
    {
        public function save()
        {
            return $this;
        }
    }
}
