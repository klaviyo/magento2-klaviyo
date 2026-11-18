<?php

namespace Magento\Framework;

if (!class_exists(\Magento\Framework\DataObject::class, false)) {
    class DataObject implements \ArrayAccess
    {
        protected $_data = [];

        public function __construct(array $data = [])
        {
            $this->_data = $data;
        }

        public function getData($key = '', $index = null)
        {
            return $this->_data[$key] ?? null;
        }

        public function setData($key, $value = null)
        {
            $this->_data[$key] = $value;
            return $this;
        }

        public function hasData($key = '')
        {
            return isset($this->_data[$key]);
        }

        public function offsetExists($offset): bool
        {
            return isset($this->_data[$offset]);
        }

        public function offsetGet($offset): mixed
        {
            return $this->_data[$offset] ?? null;
        }

        public function offsetSet($offset, $value): void
        {
            $this->_data[$offset] = $value;
        }

        public function offsetUnset($offset): void
        {
            unset($this->_data[$offset]);
        }
    }
}
