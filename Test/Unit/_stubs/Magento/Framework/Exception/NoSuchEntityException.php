<?php

namespace Magento\Framework\Exception;

require_once __DIR__ . '/LocalizedException.php';

if (!class_exists(\Magento\Framework\Exception\NoSuchEntityException::class, false)) {
    class NoSuchEntityException extends LocalizedException
    {
    }
}
