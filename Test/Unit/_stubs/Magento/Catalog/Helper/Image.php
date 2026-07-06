<?php

namespace Magento\Catalog\Helper;

if (!class_exists(\Magento\Catalog\Helper\Image::class, false)) {
    class Image
    {
        public function init($product, $imageId, $attributes = [])
        {
            return $this;
        }

        public function getUrl()
        {
            return '';
        }
    }
}
