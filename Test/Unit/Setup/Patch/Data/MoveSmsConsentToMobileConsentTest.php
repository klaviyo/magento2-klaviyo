<?php

declare(strict_types=1);

namespace Klaviyo\Reclaim\Test\Unit\Setup\Patch\Data;

use Klaviyo\Reclaim\Setup\Patch\Data\MoveSmsConsentToMobileConsent;
use Klaviyo\Reclaim\Test\Unit\Setup\Patch\Data\Stubs\SelectStub;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use PHPUnit\Framework\TestCase;

class MoveSmsConsentToMobileConsentTest extends TestCase
{
    private const ESCAPED_SMS_PATTERN = 'klaviyo!_reclaim!_consent!_at!_checkout/sms!_consent/%';

    /** @var SelectStub */
    private $selectStub;

    private function buildConnection(array $smsRows, bool $mobileRowExists): AdapterInterface
    {
        $selectStub = $this->selectStub = new SelectStub();
        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturn($selectStub);
        $connection->method('fetchAll')->willReturn($smsRows);
        $connection->method('fetchOne')->willReturn($mobileRowExists ? '1' : false);
        return $connection;
    }

    private function buildPatch(AdapterInterface $connection): MoveSmsConsentToMobileConsent
    {
        $setup = $this->createMock(ModuleDataSetupInterface::class);
        $setup->method('getConnection')->willReturn($connection);
        $setup->method('getTable')->willReturn('core_config_data');
        return new MoveSmsConsentToMobileConsent($setup);
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

    public function test_apply_handles_null_source_row_value_without_typeerror()
    {
        // core_config_data.value is nullable; strict_types=1 in the patch
        // would crash setup:upgrade if seedIfMissing rejected null values.
        $smsRows = [[
            'scope' => 'default',
            'scope_id' => '0',
            'path' => 'klaviyo_reclaim_consent_at_checkout/sms_consent/label_text',
            'value' => null,
        ]];
        $connection = $this->buildConnection($smsRows, false);
        $insertedValues = [];
        $connection->method('insert')->willReturnCallback(function ($table, array $data) use (&$insertedValues) {
            $insertedValues[$data['path']] = $data['value'];
        });
        $this->buildPatch($connection)->apply();

        $this->assertArrayHasKey(
            'klaviyo_reclaim_consent_at_checkout/mobile_consent/label_text',
            $insertedValues,
            'Null-valued source rows must still be copied (with null preserved)'
        );
        $this->assertNull($insertedValues['klaviyo_reclaim_consent_at_checkout/mobile_consent/label_text']);
    }

    public function test_apply_only_seeds_label_and_consent_defaults_at_default_scope_to_preserve_inheritance()
    {
        // Regression test for scope-inheritance breakage.
        // Merchant had is_active=1 at a store scope and label_text customized
        // at the default scope (inherited by the store). Previously pass 2
        // seeded SMS_LABEL_DEFAULT at the store scope, explicitly overriding
        // the inherited customization. Seeding only at the default scope
        // lets Magento's scope-resolution chain deliver whatever value
        // (custom or default) the parent scopes already provide.
        $smsRows = [[
            'scope' => 'stores',
            'scope_id' => '1',
            'path' => 'klaviyo_reclaim_consent_at_checkout/sms_consent/is_active',
            'value' => '1',
        ]];
        $connection = $this->buildConnection($smsRows, false);
        $insertedRows = [];
        $connection->method('insert')->willReturnCallback(function ($table, array $data) use (&$insertedRows) {
            $insertedRows[] = $data;
        });
        $this->buildPatch($connection)->apply();

        $textPaths = [
            'klaviyo_reclaim_consent_at_checkout/mobile_consent/label_text',
            'klaviyo_reclaim_consent_at_checkout/mobile_consent/consent_text',
        ];
        foreach ($insertedRows as $row) {
            if (!in_array($row['path'], $textPaths, true)) {
                continue;
            }
            $this->assertSame(
                'default',
                $row['scope'],
                'label_text/consent_text defaults must only be seeded at default scope, not ' . $row['scope']
            );
            $this->assertSame(
                '0',
                (string) $row['scope_id'],
                'label_text/consent_text defaults must only be seeded at scope_id=0'
            );
        }

        // Channels still seeded at the active scope (its own pre-migration semantics)
        $channelsRows = array_values(array_filter($insertedRows, function ($r) {
            return $r['path'] === 'klaviyo_reclaim_consent_at_checkout/mobile_consent/channels';
        }));
        $this->assertCount(1, $channelsRows);
        $this->assertSame('stores', $channelsRows[0]['scope']);
        $this->assertSame('1', (string) $channelsRows[0]['scope_id']);
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

    private function smsActiveRow(): array
    {
        return [
            'scope' => 'default',
            'scope_id' => '0',
            'path' => 'klaviyo_reclaim_consent_at_checkout/sms_consent/is_active',
            'value' => '1',
        ];
    }

    public function test_apply_deletes_all_sms_consent_rows_from_core_config_data()
    {
        $connection = $this->buildConnection([$this->smsActiveRow()], false);
        $connection->expects($this->once())
            ->method('delete')
            ->with(
                'core_config_data',
                ["path LIKE ? ESCAPE '!'" => self::ESCAPED_SMS_PATTERN]
            );
        $this->buildPatch($connection)->apply();
    }

    // Unescaped, `_` would match any character and pull in unrelated paths.
    public function test_apply_selects_sms_rows_with_escaped_like_pattern()
    {
        $connection = $this->buildConnection([], false);
        $this->buildPatch($connection)->apply();

        $this->assertSame([["path LIKE ? ESCAPE '!'", self::ESCAPED_SMS_PATTERN]], $this->selectStub->wheres);
    }

    public function test_apply_deletes_sms_rows_only_after_all_inserts()
    {
        $connection = $this->buildConnection([$this->smsActiveRow()], false);
        $calls = [];
        $connection->method('insert')->willReturnCallback(function () use (&$calls) {
            $calls[] = 'insert';
        });
        $connection->method('delete')->willReturnCallback(function () use (&$calls) {
            $calls[] = 'delete';
        });
        $this->buildPatch($connection)->apply();

        $this->assertSame(['insert', 'insert', 'insert', 'insert', 'delete'], $calls);
    }

    public function test_apply_deletes_sms_rows_even_when_all_mobile_rows_already_exist()
    {
        $connection = $this->buildConnection([$this->smsActiveRow()], true);
        $connection->expects($this->never())->method('insert');
        $connection->expects($this->once())->method('delete');
        $this->buildPatch($connection)->apply();
    }

    public function test_apply_does_not_delete_when_no_sms_rows_exist()
    {
        $connection = $this->buildConnection([], false);
        $connection->expects($this->never())->method('delete');
        $this->buildPatch($connection)->apply();
    }

    // A versioned patch is skipped once setup_version reaches its version (IES-848).
    public function test_patch_is_not_skippable_by_setup_version()
    {
        $this->assertNotContains(
            PatchVersionInterface::class,
            class_implements(MoveSmsConsentToMobileConsent::class)
        );
    }
}
