<?php

namespace Magento\Framework\Data\Form\Element\Renderer;

if (!interface_exists(\Magento\Framework\Data\Form\Element\Renderer\RendererInterface::class, false)) {
    interface RendererInterface
    {
        public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element);
    }
}
