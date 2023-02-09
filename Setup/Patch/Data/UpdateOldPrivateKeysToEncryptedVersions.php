<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Klaviyo\Reclaim\Setup\Patch\Data;

use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class UpdateOldPrivateKeysToEncryptedVersions implements DataPatchInterface, PatchVersionInterface
{
    /**
     * Klaviyo ScopeSetting
     * @var ScopeSetting $_klaviyoScopeSetting
     */
    protected $_klaviyoScopeSetting;

    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * Magento Encryptor
     * @var EncryptorInterface $_encryptor
     */
    protected $_encryptor;

    /**
     * @param ScopeSetting $_klaviyoScopeSetting
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        ScopeSetting $klaviyoScopeSettings,
        ModuleDataSetupInterface $moduleDataSetup,
        EncryptorInterface $encryptor
    ) {
        $this->_klaviyoScopeSetting = $klaviyoScopeSettings;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->_encryptor = $encryptor;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->updateOldPrivateKeysToEncryptedVersions();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    public static function getVersion()
    {
        return '1.1.7';
    }

    /**
     * in release 1.1.7 we started using the encrypted backend model for the private api key
     * this check ensures that when upgrading to this version the key is stored properly
     * otherwise we'd be trying to decrypt an unencrypted value elsewhere in the extension code (yikes)
     */
    private function updateOldPrivateKeysToEncryptedVersions()
    {
        $value = $this->_klaviyoScopeSetting->getPrivateApiKey();
        //check if there is a private key to encrypt
        if (!empty($value)) {
            //encrypt the private key
            $encrypted = $this->_encryptor->encrypt($value);
            //set the private key to the encrypted value
            $this->_klaviyoScopeSetting->setPrivateApiKey($encrypted);
        }
    }
}
