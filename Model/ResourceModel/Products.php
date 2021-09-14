<?php

namespace Klaviyo\Reclaim\Model\ResourceModel;

use Klaviyo\Reclaim\Helper\Logger;

class Products extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected $_klaviyoLogger;
    protected function _construct()
    {
        $this->_init('kl_products', 'id');
    }

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        Logger $klaviyoLogger
    )
    {
        parent::__construct($context);
        $this->_klaviyoLogger = $klaviyoLogger;
    }

    public function updateRowsToMoved($ids)
    {
        if (empty($ids)) {
            return;
        }

        $this->getConnection()->update(
            $this->getMainTable(),
            ['status' => 'MOVED'],
            ['id IN(?)' => $ids]
        );
    }

    public function deleteMovedRows($ids)
    {
        if (empty($ids)) {
            return;
        }

        $this->getConnection()->delete(
            $this->getMainTable(),
            ['id IN(?)' => $ids]
        );
    }
}
