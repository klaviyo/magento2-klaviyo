<?php

namespace Magento\Framework\Filesystem;

if (!interface_exists(\Magento\Framework\Filesystem\DriverInterface::class, false)) {
    interface DriverInterface
    {
        public function isExists($path);

        public function stat($path);

        public function isReadable($path);

        public function isFile($path);

        public function isDirectory($path);

        public function fileGetContents($path, $flag = null, $context = null);

        public function isWritable($path);

        public function getParentDirectory($path);

        public function createDirectory($path, $permissions = 0777);

        public function readDirectory($path);

        public function search($pattern, $path);

        public function rename($oldPath, $newPath, DriverInterface $targetDriver = null);

        public function copy($source, $destination, DriverInterface $targetDriver = null);

        public function symlink($source, $destination, DriverInterface $targetDriver = null);

        public function deleteFile($path);

        public function deleteDirectory($path);

        public function changePermissions($path, $permissions);

        public function touch($path, $modificationTime = null);

        public function filePutContents($path, $content, $mode = null);

        public function fileOpen($path, $mode);

        public function fileReadLine($resource, $length, $ending = null);

        public function fileRead($resource, $length);

        public function fileTell($resource);

        public function fileSeek($resource, $offset, $whence = SEEK_SET);

        public function endOfFile($resource);

        public function fileClose($resource);

        public function fileWrite($resource, $data);

        public function getAbsolutePath($basePath, $path, $scheme = null);

        public function getRealPath($path);

        public function getRelativePath($basePath, $path = null);
    }
}
