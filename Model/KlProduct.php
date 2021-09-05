<?php

namespace Klaviyo\Reclaim\Model;

use Magento\Framework\Model\AbstractModel;

class KlProduct extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
      $this->_init('Klaviyo\Reclaim\Model\ResourceModel\KlProduct');
      parent::_construct();
    }
}
