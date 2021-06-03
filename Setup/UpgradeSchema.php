<?php


namespace Klaviyo\Reclaim\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Quote\Setup\QuoteSetupFactory;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Framework\DB\Ddl\Table;
use Magento\Quote\Setup\QuoteSetup;
use Magento\Sales\Setup\SalesSetup;

class UpgradeSchema implements UpgradeSchemaInterface
{

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '2.1.1', '<')) {
            $installer = $setup;
            $installer->startSetup();

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

            foreach ($columns as $name => $definition) {
                $connection->addColumn($eavTable1, $name, $definition);
                $connection->addColumn($eavTable2, $name, $definition);
            }

            $installer->endSetup();
        }

    }
}
