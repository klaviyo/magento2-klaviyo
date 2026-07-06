<?php

namespace Magento\Quote\Model;

if (!class_exists(\Magento\Quote\Model\QuoteFactory::class, false)) {
    class QuoteFactory
    {
        public function create(array $data = [])
        {
            return null;
        }
    }
}
