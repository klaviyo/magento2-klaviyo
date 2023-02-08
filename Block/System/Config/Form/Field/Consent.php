<?php

namespace Klaviyo\Reclaim\Block\System\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;

class Consent extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(AbstractElement $element)
    {
        $values = $element->getValues();

        if (sizeof($values) > 1) {
            return parent::_getElementHtml($element);
        }

        $html = '<strong style="color: red">Please set your private api key in the <a id="kl-general-tab" href="">General</a> tab.</strong>
        <script type="text/javascript">
            var generalTab = window.location.href.replace("klaviyo_reclaim_consent_at_checkout", "klaviyo_reclaim_general");
            document.getElementById("kl-general-tab").setAttribute("href", generalTab);
        </script>';

        return $html;
    }
}
