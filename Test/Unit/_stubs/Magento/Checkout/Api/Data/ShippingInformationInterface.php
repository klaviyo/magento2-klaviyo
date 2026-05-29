<?php

namespace Magento\Checkout\Api\Data;

if (!interface_exists(\Magento\Checkout\Api\Data\ShippingInformationInterface::class, false)) {
    interface ShippingInformationInterface
    {
        public function getExtensionAttributes();
    }
}
