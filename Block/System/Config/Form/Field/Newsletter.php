<?php

namespace Klaviyo\Reclaim\Block\System\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;

class Newsletter extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(AbstractElement $element)
    {
        $values = $element->getValues();

        if (sizeof($values) > 1) {
            return parent::_getElementHtml($element);
        }

        $message =  '<p>' . $values[0]['label'] . '</p>';

        $html = '<script type="text/javascript">
            var el = document.getElementById("klaviyo_reclaim_newsletter_newsletter");
            el.outerHTML = \'' . $message . '\';
        </script>';

        return $html;
    }
}
