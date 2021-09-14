<?php

namespace Klaviyo\Reclaim\Model;

use Magento\Framework\Model\AbstractModel;

class Syncs extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
      $this->_init('Klaviyo\Reclaim\Model\ResourceModel\Syncs');
      parent::_construct();
    }
}
