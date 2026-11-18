<?php

namespace Magento\Framework;

if (!class_exists(\Magento\Framework\Event::class, false)) {
    class Event
    {
        public function getOrder()
        {
            return null;
        }
        public function getQuote()
        {
            return null;
        }
    }
}
