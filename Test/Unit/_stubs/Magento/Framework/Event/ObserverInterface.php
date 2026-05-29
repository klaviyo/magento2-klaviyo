<?php

namespace Magento\Framework\Event;

if (!interface_exists(\Magento\Framework\Event\ObserverInterface::class, false)) {
    interface ObserverInterface
    {
        public function execute(Observer $observer);
    }
}
