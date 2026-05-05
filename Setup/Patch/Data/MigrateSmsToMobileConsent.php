<?php

declare(strict_types=1);

namespace Klaviyo\Reclaim\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

class MigrateSmsToMobileConsent implements DataPatchInterface, PatchVersionInterface
{
    private const SMS_CONSENT_PREFIX = 'klaviyo_reclaim_consent_at_checkout/sms_consent/';
    private const MOBILE_CHANNELS_PATH = 'klaviyo_reclaim_consent_at_checkout/mobile_consent/channels';

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        $table = $this->moduleDataSetup->getTable('core_config_data');

        $select = $connection->select()
            ->from($table)
            ->where('path LIKE ?', self::SMS_CONSENT_PREFIX . '%');
        $smsRows = $connection->fetchAll($select);

        foreach ($smsRows as $row) {
            $mobilePath = str_replace('sms_consent/', 'mobile_consent/', $row['path']);

            if (!$this->rowExists($connection, $table, $row['scope'], (int)$row['scope_id'], $mobilePath)) {
                $connection->insert($table, [
                    'scope'    => $row['scope'],
                    'scope_id' => $row['scope_id'],
                    'path'     => $mobilePath,
                    'value'    => $row['value'],
                ]);
            }

            // When SMS is_active was enabled, seed the channels field with 'sms'
            if (strpos($row['path'], '/is_active') !== false && $row['value'] == '1') {
                if (!$this->rowExists($connection, $table, $row['scope'], (int)$row['scope_id'], self::MOBILE_CHANNELS_PATH)) {
                    $connection->insert($table, [
                        'scope'    => $row['scope'],
                        'scope_id' => $row['scope_id'],
                        'path'     => self::MOBILE_CHANNELS_PATH,
                        'value'    => 'sms',
                    ]);
                }
            }
        }

        $connection->endSetup();
        return $this;
    }

    public function getAliases()
    {
        return [];
    }

    public static function getDependencies()
    {
        return [UpdateOldPrivateKeysToEncryptedVersions::class];
    }

    public static function getVersion()
    {
        return '4.5.0';
    }

    private function rowExists($connection, string $table, string $scope, int $scopeId, string $path): bool
    {
        $select = $connection->select()
            ->from($table, ['config_id'])
            ->where('scope = ?', $scope)
            ->where('scope_id = ?', $scopeId)
            ->where('path = ?', $path);
        return (bool) $connection->fetchOne($select);
    }
}
