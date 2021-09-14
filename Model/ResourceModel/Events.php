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

    public function getIdsToDelete()
    {
        $now = new \DateTime('now');
        $date = $now->sub( new \DateInterval('P2D') )->format('Y-m-d H:i:s');

        $tableName = $this->getMainTable();

        return $this->getConnection()->fetchAll( "select id from (
                                                            select id, timestampdiff(day, created_at, \"$date\") as row_age_in_days
                                                            from $tableName
                                                            where status = '".self::MOVED."'
                                                            having row_age_in_days > 2
                                                          ) as age_of_rows;"
        );

    }
}
