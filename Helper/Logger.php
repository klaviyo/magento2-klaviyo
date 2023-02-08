<?php

namespace Klaviyo\Reclaim\Helper;

use Magento\Framework\Filesystem\DirectoryList;
use Klaviyo\Reclaim\Logger\Logger as KlaviyoLogger;
use Klaviyo\Reclaim\Helper\ScopeSetting;

class Logger
{
    /**
     * directory list interface
     * used to programmatically retrieve paths within magento app install
     * @var DirectoryList
     */
    protected $_dir;

    /**
     * path to the log file
     * @var string
     */
    protected $_logPath;

    /**
     * klaviyo logger object
     * @var KlaviyoLogger
     */
    protected $_klaviyoLogger;

    /**
     * is the logger enabled?
     * @var boolean
     */
    protected $_loggerEnabled;

    public function __construct(
        DirectoryList $dir,
        KlaviyoLogger $klaviyoLogger,
        ScopeSetting $klaviyoScopeSetting,
        $logPath = null
    ) {
        $this->_dir = $dir;
        $this->_klaviyoLogger = $klaviyoLogger;
        $this->_loggerEnabled = $klaviyoScopeSetting->isLoggerEnabled();
        $this->_logPath = (!empty($logPath)) ? $logPath : $this->_dir->getPath('log') . '/klaviyo.log';
    }

    /**
     * Getter method for the logfile's path
     * @return string
     */
    public function getPath()
    {
        return $this->_logPath;
    }

    /**
     * Method to log the provided message
     * @param string $message
     */
    public function log($message)
    {
        if ($this->_loggerEnabled) {
            $this->_klaviyoLogger->info($message);
        }
    }
}
