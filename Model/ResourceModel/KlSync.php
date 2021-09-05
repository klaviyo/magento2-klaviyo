<?php

namespace Klaviyo\Reclaim\Model\ResourceModel;

class KlSync extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
      $this->_init('kl_sync', 'id');
    }

    public function updateRowsToSynced($ids)
    {
      if (empty($ids)) {
          return;
      }

      $this->getConnection()->update(
          $this->getMainTable(),
          ['status' => 'SYNCED'],
          $where = ['id IN(?)' => $ids]
      );
    }

    public function deleteSyncedRows($ids)
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
