<?php

namespace Klaviyo\Reclaim\Model;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Klaviyo abstract collection class used for inheritance
 */
abstract class KlaviyoCollection extends AbstractCollection
{
    /**
     * Collection Helper method to get rows to be moved to sync table or send over to Klaviyo app
     * Takes in status to construct the where query and returns 500 rows
     * @param $status
     * @return mixed
     */
    public function getRowsForSync($status)
    {
        $syncCollection = $this->addFieldToFilter('status', $status)
            ->addOrder('id', self::SORT_ORDER_ASC)
            ->setPageSize(500);

        return $syncCollection;
    }

    /**
     * Collection Helper method to get rows to be deleted.
     * Fetches all rows older than 2 days and having status `FAILED` as well as`SYNCED` for sync table, `MOVED` for topic tables
     * and returns ids of these rows
     * @param $statusesToDelete
     * @return mixed
     */
    public function getIdsToDelete($statusesToDelete)
    {
        $now = new \DateTime('now');
        $date = $now->sub(new \DateInterval('P2D'))
            ->format('Y-m-d H:i:s');

        $tableName = $this->getMainTable();
        $statusList = '("' . implode('","', $statusesToDelete) . '")';

        $idsToDelete = $this->getConnection()->fetchAll("select id from (
                                                            select id, timestampdiff(day, created_at, \"$date\") as row_age_in_days
                                                            from $tableName
                                                            where status in $statusList
                                                            having row_age_in_days > 2
                                                          ) as age_of_rows;");

        return $idsToDelete;
    }

    /**
     * Collection Helper method to update statuses of rows that have been moved or attempted sync to Klaviyo app
     * Takes in array of ids and what status to update them to as arguments
     * @param $ids
     * @param $status
     */
    public function updateRowStatus($ids, $status)
    {
        if (empty($ids)) {
            return;
        }

        $this->getConnection()->update(
            $this->getMainTable(),
            $bind = ['status' => $status],
            $where = ['id IN(?)' => $ids]
        );
    }

    /**
     * Collection Helper method to delete rows that have been deemed fit for removal.
     * @param $ids
     */
    public function deleteRows($ids)
    {
        if (empty($ids)) {
            return;
        }

        $this->getConnection()->delete(
            $this->getMainTable(),
            $where = ['id IN(?)' => $ids]
        );
    }
}
