<?php

if (!function_exists('__')) {
    function __($text, ...$args)
    {
        return new \Magento\Framework\Phrase($text, $args);
    }
}
