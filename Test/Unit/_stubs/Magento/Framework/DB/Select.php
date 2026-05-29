<?php

namespace Magento\Framework\DB;

if (!class_exists(\Magento\Framework\DB\Select::class, false)) {
    class Select
    {
        public function from($table, $cols = '*')
        {
            return $this;
        }
        public function where($condition, $value = null)
        {
            return $this;
        }
    }
}
