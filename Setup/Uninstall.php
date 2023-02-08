<?php

namespace Klaviyo\Reclaim\Setup;

use Magento\Framework\Setup\UninstallInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Klaviyo\Reclaim\Helper\Logger;

class Uninstall implements UninstallInterface
{
    /**
     * Logging helper
     * @var \Klaviyo\Reclaim\Helper\Logger
     */
    protected $_klaviyoLogger;

    public function __construct(
        Logger $klaviyoLogger
    ) {
        $this->_klaviyoLogger = $klaviyoLogger;
    }

    public function uninstall(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        //remove the Klaviyo log file
        $path = $this->_klaviyoLogger->getPath();
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
