<?php

namespace Klaviyo\Reclaim\Model;

use Magento\Framework\Model\AbstractModel;

class Products extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Klaviyo\Reclaim\Model\ResourceModel\Products');
        parent::_construct();
    }
}
