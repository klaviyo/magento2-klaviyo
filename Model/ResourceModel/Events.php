<?php

namespace Klaviyo\Reclaim\Model\Resourcemodel;

use Klaviyo\Reclaim\Setup\SchemaInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Events extends AbstractDb
{
    const MOVED = 'MOVED';

    protected function _construct()
    {
        $this->_init(SchemaInterface::KL_EVENTS_TOPIC_TABLE, 'id');
    }

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    )
    {
        parent::__construct($context);
    }

    public function updateRowsToMoved($ids)
    {
        if (empty($ids)) {
            return;
        }

        $bind = ['status' => self::MOVED];

        $where = ['id IN(?)' => $ids];
        $this->getConnection()->update(
            $this->getMainTable(),
            $bind,
            $where
        );
    }

    public function deleteMovedRows($ids)
    {
        if (empty($ids)) {
            return;
        }

        $where = ['id IN(?)' => $ids];

        $this->getConnection()->delete(
            $this->getMainTable(),
            $where
        );
    }
}
