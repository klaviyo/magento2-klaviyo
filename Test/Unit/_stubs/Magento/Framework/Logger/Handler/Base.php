<?php

namespace Magento\Framework\Logger\Handler;

use Magento\Framework\Filesystem\DriverInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

if (!class_exists(\Magento\Framework\Logger\Handler\Base::class, false)) {
    class Base extends StreamHandler
    {
        protected $fileName = '';
        protected $loggerType = Logger::DEBUG;
        protected $filesystem;

        public function __construct(DriverInterface $filesystem, $filePath = null, $fileName = null)
        {
            $this->filesystem = $filesystem;

            if (!empty($fileName)) {
                $this->fileName = $fileName;
            }

            parent::__construct(
                $filePath ? $filePath . $this->fileName : $this->fileName,
                $this->loggerType
            );

            $this->setFormatter(new LineFormatter(null, null, true));
        }
    }
}
