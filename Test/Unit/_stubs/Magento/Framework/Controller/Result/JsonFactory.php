<?php

namespace Magento\Framework\Controller\Result;

if (!class_exists(\Magento\Framework\Controller\Result\JsonFactory::class, false)) {
    class JsonFactory
    {
        public function create(array $data = [])
        {
            return new Json();
        }
    }
}
