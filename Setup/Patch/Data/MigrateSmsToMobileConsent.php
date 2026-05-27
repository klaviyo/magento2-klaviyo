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
    private const MOBILE_LABEL_TEXT_PATH = 'klaviyo_reclaim_consent_at_checkout/mobile_consent/label_text';
    private const MOBILE_CONSENT_TEXT_PATH = 'klaviyo_reclaim_consent_at_checkout/mobile_consent/consent_text';

    // SMS-specific Figma defaults — backfilled for merchants upgrading from
    // SMS-only consent who never customized these fields. Without this
    // backfill they'd fall through to the new mobile_consent defaults in
    // config.xml (which are "both"-channel copy mentioning WhatsApp).
    // Canonical source: view/adminhtml/web/js/mobile-consent-form.js (CONTENT.sms).
    private const SMS_LABEL_DEFAULT = 'Check this box to receive promotional marketing texts (Exclusive text messaging-only deals, offers, and coupons)';
    private const SMS_CONSENT_DEFAULT = 'By checking this box and entering your phone number, you consent to receive informational (e.g., order updates) and/or marketing texts (e.g., cart reminders) from [company name] including texts sent by autodialer. Consent is not a condition of purchase. Msg & data rates may apply. Msg frequency varies. Unsubscribe at any time by replying STOP or clicking the unsubscribe link (where available). Privacy Policy [link] & Terms [link].';

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

        // Two-pass migration. Pass 1 copies every existing sms_consent row to
        // its mobile_consent equivalent. Pass 2 backfills SMS defaults for
        // active-SMS merchants who never customized label/disclosure copy.
        // Pass 1 must complete before pass 2 so merchant customizations are
        // never shadowed by a seed-default (which would happen if iteration
        // hit /is_active before /label_text in config_id order).

        foreach ($smsRows as $row) {
            $mobilePath = str_replace('sms_consent/', 'mobile_consent/', $row['path']);
            $this->seedIfMissing($connection, $table, $row['scope'], (int)$row['scope_id'], $mobilePath, $row['value']);
        }

        foreach ($smsRows as $row) {
            if (strpos($row['path'], '/is_active') !== false && $row['value'] == '1') {
                $this->seedIfMissing($connection, $table, $row['scope'], (int)$row['scope_id'], self::MOBILE_CHANNELS_PATH, 'sms');
                $this->seedIfMissing($connection, $table, $row['scope'], (int)$row['scope_id'], self::MOBILE_LABEL_TEXT_PATH, self::SMS_LABEL_DEFAULT);
                $this->seedIfMissing($connection, $table, $row['scope'], (int)$row['scope_id'], self::MOBILE_CONSENT_TEXT_PATH, self::SMS_CONSENT_DEFAULT);
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

    private function seedIfMissing($connection, string $table, string $scope, int $scopeId, string $path, string $value): void
    {
        if ($this->rowExists($connection, $table, $scope, $scopeId, $path)) {
            return;
        }
        $connection->insert($table, [
            'scope'    => $scope,
            'scope_id' => $scopeId,
            'path'     => $path,
            'value'    => $value,
        ]);
    }
}
