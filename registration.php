<?php

use Magento\Framework\Component\ComponentRegistrar;

if (class_exists('ComponentRegistrar')) {
    ComponentRegistrar::register(
        ComponentRegistrar::MODULE,
        'Klaviyo_Reclaim',
        __DIR__
    );
}
