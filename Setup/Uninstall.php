<?php
namespace Klaviyo\Reclaim\Setup;

use Magento\Framework\Setup\UninstallInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Filesystem\DirectoryList;

class Uninstall implements UninstallInterface
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

    public function uninstall(
        SchemaSetupInterface $setup, ModuleContextInterface $context
    )
    {
        //remove the Klaviyo log file
        $path = $this->_dir->getPath('log') . '/klaviyo.log';
        if (file_exists($path)) {
            unlink($path);
        }
    }
}