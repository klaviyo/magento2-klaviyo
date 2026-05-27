<?php

declare(strict_types=1);

// Stub Magento framework interfaces so MigrateSmsToMobileConsent can load without the full Magento stack.
namespace Magento\Framework\Setup\Patch {
    if (!interface_exists(\Magento\Framework\Setup\Patch\DataPatchInterface::class)) {
        interface DataPatchInterface
        {
            public function apply();
            public function getAliases();
            public static function getDependencies();
        }
    }
    if (!interface_exists(\Magento\Framework\Setup\Patch\PatchVersionInterface::class)) {
        interface PatchVersionInterface
        {
            public static function getVersion();
        }
    }
}

namespace Magento\Framework\Setup {
    if (!interface_exists(\Magento\Framework\Setup\ModuleDataSetupInterface::class)) {
        interface ModuleDataSetupInterface
        {
            public function getConnection();
            public function getTable($tableName);
            public function startSetup();
            public function endSetup();
        }
    }
}

namespace Magento\Framework\DB {
    if (!class_exists(\Magento\Framework\DB\Select::class)) {
        class Select
        {
            public function from($table, $cols = '*')
            {
                return $this;
            }
            public function where($condition, $value = null)
            {
                return $this;
            }
        }
    }
}

namespace Klaviyo\Reclaim\Test\Unit\Setup\Patch\Data\Stubs {
    /**
     * Query-builder shim used by the test mock. Cannot reuse
     * Magento\Framework\DB\Select directly because the real Magento class
     * (when autoloaded in a full Magento test environment) requires
     * constructor args, which breaks the test stub instantiation.
     */
    class SelectStub
    {
        public function from($table, $cols = '*')
        {
            return $this;
        }
        public function where($condition, $value = null)
        {
            return $this;
        }
    }
}

namespace Magento\Framework\DB\Adapter {
    if (!interface_exists(\Magento\Framework\DB\Adapter\AdapterInterface::class)) {
        interface AdapterInterface
        {
            public function startSetup();
            public function endSetup();
            public function select();
            public function fetchAll($select);
            public function fetchOne($select);
            public function insert($table, array $data);
        }
    }
}

namespace Klaviyo\Reclaim\Test\Unit\Setup\Patch\Data {

    use Klaviyo\Reclaim\Setup\Patch\Data\MigrateSmsToMobileConsent;
    use Klaviyo\Reclaim\Test\Unit\Setup\Patch\Data\Stubs\SelectStub;
    use Magento\Framework\DB\Adapter\AdapterInterface;
    use Magento\Framework\Setup\ModuleDataSetupInterface;
    use PHPUnit\Framework\TestCase;

    class MigrateSmsToMobileConsentTest extends TestCase
    {
        private function buildConnection(array $smsRows, bool $mobileRowExists): AdapterInterface
        {
            $selectStub = new SelectStub();
            $connection = $this->createMock(AdapterInterface::class);
            $connection->method('select')->willReturn($selectStub);
            $connection->method('fetchAll')->willReturn($smsRows);
            $connection->method('fetchOne')->willReturn($mobileRowExists ? '1' : false);
            return $connection;
        }

        private function buildPatch(AdapterInterface $connection): MigrateSmsToMobileConsent
        {
            $setup = $this->createMock(ModuleDataSetupInterface::class);
            $setup->method('getConnection')->willReturn($connection);
            $setup->method('getTable')->willReturn('core_config_data');
            return new MigrateSmsToMobileConsent($setup);
        }

        public function test_apply_writes_nothing_when_no_sms_rows_exist()
        {
            $connection = $this->buildConnection([], false);
            $connection->expects($this->never())->method('insert');
            $this->buildPatch($connection)->apply();
        }

        public function test_apply_inserts_mobile_consent_rows_including_channels_and_sms_backfill_when_sms_is_active_1()
        {
            $smsRows = [[
                'scope' => 'default',
                'scope_id' => '0',
                'path' => 'klaviyo_reclaim_consent_at_checkout/sms_consent/is_active',
                'value' => '1',
            ]];
            $connection = $this->buildConnection($smsRows, false);
            // Expect 4 inserts: mobile_consent/is_active, /channels, /label_text, /consent_text
            $connection->expects($this->exactly(4))->method('insert');
            $this->buildPatch($connection)->apply();
        }

        public function test_apply_seeds_sms_specific_label_and_consent_text_when_no_source_rows_exist()
        {
            $smsRows = [[
                'scope' => 'default',
                'scope_id' => '0',
                'path' => 'klaviyo_reclaim_consent_at_checkout/sms_consent/is_active',
                'value' => '1',
            ]];
            $connection = $this->buildConnection($smsRows, false);
            $insertedPaths = [];
            $insertedValues = [];
            $connection->method('insert')->willReturnCallback(function ($table, array $data) use (&$insertedPaths, &$insertedValues) {
                $insertedPaths[] = $data['path'];
                $insertedValues[$data['path']] = $data['value'];
            });
            $this->buildPatch($connection)->apply();

            $this->assertContains('klaviyo_reclaim_consent_at_checkout/mobile_consent/label_text', $insertedPaths);
            $this->assertContains('klaviyo_reclaim_consent_at_checkout/mobile_consent/consent_text', $insertedPaths);

            $this->assertStringContainsString(
                'Exclusive text messaging-only',
                $insertedValues['klaviyo_reclaim_consent_at_checkout/mobile_consent/label_text']
            );
            $this->assertStringContainsString(
                'marketing texts (e.g., cart reminders)',
                $insertedValues['klaviyo_reclaim_consent_at_checkout/mobile_consent/consent_text']
            );
        }

        public function test_apply_does_not_insert_when_mobile_rows_already_exist()
        {
            $smsRows = [[
                'scope' => 'default',
                'scope_id' => '0',
                'path' => 'klaviyo_reclaim_consent_at_checkout/sms_consent/is_active',
                'value' => '1',
            ]];
            $connection = $this->buildConnection($smsRows, true);
            $connection->expects($this->never())->method('insert');
            $this->buildPatch($connection)->apply();
        }

        public function test_apply_preserves_custom_label_text_when_is_active_iterated_before_label_text()
        {
            // Regression test for two-pass migration ordering.
            // Merchant set is_active=1 first (lower config_id) and later
            // customized label_text. Single-pass logic would have seeded the
            // SMS default label_text during the is_active iteration before
            // reaching the merchant's customized row, silently dropping the
            // customization. Two-pass logic copies all source rows first, then
            // backfills only what's still missing.
            $smsRows = [
                [
                    'scope' => 'default',
                    'scope_id' => '0',
                    'path' => 'klaviyo_reclaim_consent_at_checkout/sms_consent/is_active',
                    'value' => '1',
                ],
                [
                    'scope' => 'default',
                    'scope_id' => '0',
                    'path' => 'klaviyo_reclaim_consent_at_checkout/sms_consent/label_text',
                    'value' => 'My Custom SMS Copy',
                ],
            ];
            $connection = $this->buildConnection($smsRows, false);
            $firstValueByPath = [];
            $connection->method('insert')->willReturnCallback(function ($table, array $data) use (&$firstValueByPath) {
                if (!isset($firstValueByPath[$data['path']])) {
                    $firstValueByPath[$data['path']] = $data['value'];
                }
            });
            $this->buildPatch($connection)->apply();

            $this->assertSame(
                'My Custom SMS Copy',
                $firstValueByPath['klaviyo_reclaim_consent_at_checkout/mobile_consent/label_text'] ?? null,
                'Custom merchant label_text must be copied before the SMS default backfill runs'
            );
        }
    }
}
