<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Klaviyo\Reclaim\Setup;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        /**
         * Create products table
         */
        $this->createProductsTable($installer);

        /**
         * Add consent columns to quote and sales_order tables
         */
        $eavTable1 = $installer->getTable('quote');
        $eavTable2 = $installer->getTable('sales_order');

        $columns = [
            'kl_sms_consent' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'SMS Consent'
            ],
            'kl_email_consent' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Email Consent'
            ],
        ];

        $connection = $installer->getConnection();

        foreach($columns as $name => $definition) {
            $connection->addColumn($eavTable1, $name, $definition);
            $connection->addColumn($eavTable2, $name, $definition);
        }

        $installer->endSetup();
    }

    /**
     * @param SchemaSetupInterface $installer
     *
     * @return null
     */
    private function createProductsTable($installer)
    {
      $tableName = 'kl_products';
      $this->dropTableIfExists($installer, $tableName);

      $productsTable = $installer->getConnection()->newTable($tableName);
      $productsTable = $this->addColumnsToProductsTable($productsTable);
      // $productsTable = $this->addIndexesToProductsTable($installer, $productsTable);
    }

    /**
     * @param Table $productsTable
     * @return Table
     */
    private function addColumnsToProductsTable($productsTable)
    {
      return $productsTable->addColumn(
        'id',
        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
        null,
        [
          'primary' => true,
          'identity' => true,
          'unsigned' => true,
          'nullable' => false
        ],
        'Primary Key'
      )
      ->addColumn(
        'payload',
        \Magento\Framework\DB\Ddl\Table::TYPE_JSON,
        null,
        ['nullable' => false],
        'Payload'
      )
      ->addColumn(
        'created_at',
        \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
        null,
        [],
        'Created at Time'
      )
      ->addColumn(
        'updated_at',
        \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
        null,
        [],
        'Updated at Time'
      )
    }
}
