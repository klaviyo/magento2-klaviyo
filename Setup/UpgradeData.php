<?php
namespace Klaviyo\Reclaim\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $_encryptor;

    /**
     * Logging helper
     * @var \Klaviyo\Reclaim\Helper\Logger
     */
    protected $_klaviyoLogger;

    /**
     * @var \Klaviyo\Reclaim\Helper\ScopeSetting
     */
    protected $_klaviyoScopeSetting;

    /**
     * @var \Magento\Framework\App\State 
     */
    protected $_state;

    /**
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Klaviyo\Reclaim\Helper\Logger $klaviyoLogger
     * @param \Klaviyo\Reclaim\Helper\ScopeSetting $klaviyoScopeSetting
     * @param \Magento\Framework\App\State $state
     */
    public function __construct(
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Klaviyo\Reclaim\Helper\Logger $klaviyoLogger,
        \Klaviyo\Reclaim\Helper\ScopeSetting $klaviyoScopeSetting,
        \Magento\Framework\App\State $state
    )
    {
        $this->_encryptor = $encryptor;
        $this->_klaviyoLogger = $klaviyoLogger;
        $this->_klaviyoScopeSetting = $klaviyoScopeSetting;
        $this->_state = $state;
    }

    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    )
    {
        try{
            $this->_state->getAreaCode();
        }
        catch (\Magento\Framework\Exception\LocalizedException $ex) {
            $this->_state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        }

        $setup->startSetup();

        /**
         * in release 1.1.7 we started using the encrypted backend model for the private api key
         * this check ensures that when upgrading to this version the key is stored properly
         * otherwise we'd be trying to decrypt an unencrypted value elsewhere in the extension code (yikes)
         */
        if (version_compare($context->getVersion(), '1.1.7', '<')) {
            //retrieve current key (unencrypted)
            $value = $this->_klaviyoScopeSetting->getPrivateApiKey();
            //check if there is a private key to encrypt
            if (!empty($value)) {
                //encrypt the private key
                $encrypted = $this->_encryptor->encrypt($value);
                //set the private key to the encrypted value
                $this->_klaviyoScopeSetting->setPrivateApiKey($encrypted);
            }
        }
        $setup->endSetup();
    }
}
