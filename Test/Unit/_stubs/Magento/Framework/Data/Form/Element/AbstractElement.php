<?php

namespace Magento\Framework\Data\Form\Element;

require_once __DIR__ . '/../../../DataObject.php';

if (!class_exists(\Magento\Framework\Data\Form\Element\AbstractElement::class, false)) {
    class AbstractElement extends \Magento\Framework\DataObject
    {
        public function getValues()
        {
            return [];
        }
    }
}
