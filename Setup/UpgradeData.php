<?php
namespace Klaviyo\Reclaim\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use \Klaviyo\Reclaim\Helper\Data as DataHelper;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $_encryptor;

    /**
     * @var DataHelper
     */
    protected $_dataHelper;

    /**
     * @var \Magento\Framework\App\State 
     */
    protected $_state;

    /**
     * @param DataHelper $dataHelper
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Framework\App\State $state
     */
    public function __construct(
        DataHelper $dataHelper,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\App\State $state
    )
    {
        $this->_dataHelper = $dataHelper;
        $this->_encryptor = $encryptor;
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
            $this->_state->setAreaCode('adminhtml');
        }

        $setup->startSetup();
        /**
         * in release 1.1.7 we started using the encrypted backend model for the private api key
         * this check ensures that when upgrading to this version the key is stored properly
         * otherwise we'd be trying to decrypt an unencrypted value elsewhere in the extension code (yikes)
         */
        if (version_compare($context->getVersion(), '1.1.7', '<')) {
            //retrieve current key (unencrypted)
            $value = $this->_dataHelper->getPrivateApiKey();
            //encrypt the private key
            $encrypted = $this->_encryptor->encrypt($value);
            //set the private key to the encrypted value
            $this->_dataHelper->setPrivateApiKey($encrypted);
        }
        $setup->endSetup();
    }
}