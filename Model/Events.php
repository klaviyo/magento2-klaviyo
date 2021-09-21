<?php

namespace Klaviyo\Reclaim\Model;

use Magento\Framework\Model\AbstractModel;

class Events extends AbstractModel
{
    protected function _construct()
    {
        $this->_init('Klaviyo\Reclaim\Model\Resourcemodel\Events');
        parent::_construct();
    }
}
