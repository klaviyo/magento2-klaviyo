<?php
namespace Klaviyo\Reclaim\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Klaviyo\Reclaim\Helper\Logger;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * Logging helper
     * @var \Klaviyo\Reclaim\Helper\Logger
     */
    protected $_klaviyoLogger;

    public function __construct(
        Logger $klaviyoLogger
    )
    {
        $this->_klaviyoLogger = $klaviyoLogger;
    }

    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    )
    {
        //Klaviyo log file creation
        $path = $this->_klaviyoLogger->getPath();
        if (!file_exists($path)) {
            fopen($path, 'w');
        }
        chmod($path, 0644);
    }
}