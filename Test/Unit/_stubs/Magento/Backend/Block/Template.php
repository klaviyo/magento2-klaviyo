<?php

namespace Magento\Backend\Block;

require_once __DIR__ . '/../../Framework/View/Element/Template.php';

if (!class_exists(\Magento\Backend\Block\Template::class, false)) {
    class Template extends \Magento\Framework\View\Element\Template
    {
    }
}
