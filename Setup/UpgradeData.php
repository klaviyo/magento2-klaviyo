<?php
namespace Klaviyo\Reclaim\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Filesystem\DirectoryList;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * DirectoryList instance
     * @var \Magento\Framework\Filesystem\DirectoryList $_dir
     */
    protected $_dir;

    public function __construct(
        DirectoryList $dir
    )
    {
        $this->_dir = $dir;
    }

    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    )
    {
        //Klaviyo log file creation
        $path = $this->_dir->getPath('log') . '/klaviyo.log';
        if (!file_exists($path)) {
            fopen($path, 'w');
        }
        chmod($path, 0644);
    }
}