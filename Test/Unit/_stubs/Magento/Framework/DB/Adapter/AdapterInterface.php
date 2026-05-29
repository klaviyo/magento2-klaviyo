<?php

namespace Magento\Framework\DB\Adapter;

if (!interface_exists(\Magento\Framework\DB\Adapter\AdapterInterface::class, false)) {
    interface AdapterInterface
    {
        public function startSetup();
        public function endSetup();
        public function select();
        public function fetchAll($select);
        public function fetchOne($select);
        public function insert($table, array $data);
    }
}
