<?php

namespace Magento\Framework\Event;

if (!class_exists(\Magento\Framework\Event\Observer::class, false)) {
    class Observer
    {
        public function getEvent()
        {
            return new \Magento\Framework\Event();
        }

        public function getDataObject()
        {
            return null;
        }
    }
}
