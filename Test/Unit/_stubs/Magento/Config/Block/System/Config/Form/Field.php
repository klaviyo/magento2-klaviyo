<?php

namespace Magento\Config\Block\System\Config\Form;

require_once __DIR__ . '/../../../../../Backend/Block/Template.php';
require_once __DIR__ . '/../../../../../Backend/Block/Template/Context.php';
require_once __DIR__ . '/../../../../../Framework/Data/Form/Element/Renderer/RendererInterface.php';

if (!class_exists(\Magento\Config\Block\System\Config\Form\Field::class, false)) {
    class Field extends \Magento\Backend\Block\Template implements
        \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
    {
        public function __construct(
            \Magento\Backend\Block\Template\Context $context,
            array $data = []
        ) {
            parent::__construct($context, $data);
        }

        public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
        {
            return '';
        }
    }
}
