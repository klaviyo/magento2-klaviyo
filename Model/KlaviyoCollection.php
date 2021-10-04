<?php

namespace Klaviyo\Reclaim\Model;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Klaviyo abstract collection class used for inheritance
 */
abstract class KlaviyoCollection extends AbstractCollection
{
    public function getRowsForSync($status)
    {
        $syncCollection = $this->addFieldToFilter('status',$status)
            ->addOrder('id',self::SORT_ORDER_ASC)
            ->setPageSize(500);

        return $syncCollection;
    }

    public function getIdsToDelete($status)
    {
        $now = new \DateTime('now');
        $date = $now->sub(new \DateInterval('P2D'))
            ->format('Y-m-d H:i:s');

        $tableName = $this->getMainTable();

        $idsToDelete = $this->getConnection()->fetchAll( "select id from (
                                                            select id, timestampdiff(day, created_at, \"$date\") as row_age_in_days
                                                            from $tableName
                                                            where status = '".$status."'
                                                            having row_age_in_days > 2
                                                          ) as age_of_rows;"
        );

        return $idsToDelete;
    }

    public function updateRowStatus($ids, $status)
    {
        if (empty($ids)) {return;}

        $this->getConnection()->update(
            $this->getMainTable(),
            $bind = ['status' => $status],
            $where = ['id IN(?)' => $ids]
        );
    }

    public function deleteRows($ids)
    {
        if (empty($ids)) {return;}

        $this->getConnection()->delete(
            $this->getMainTable(),
            $where = ['id IN(?)' => $ids]
        );
    }
}
