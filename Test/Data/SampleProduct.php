<?php

namespace Klaviyo\Reclaim\Test\Data;

class SampleProduct
{
    /**
     * sample product data to be used in tests
     */
    const PRODUCT_ID = 1234;
    const PRODUCT_NAME = 'Test Product';
    const PRODUCT_SKU = 'TEST_PRODUCT_1234';
    const PRODUCT_URL = 'https://www.example.com/test-product-1234';
    const PRODUCT_IMAGE_URL = 'https://www.example.com/media/catalog/product/cache/image/698x900/asdf/placeholder/default/base.jpg';
    const PRODUCT_PRICE = 543.21;
    const PRODUCT_PRICE_FINAL = 123.45;
    const PRODUCT_CATEGORY_IDS = [
        111,
        222,
        333,
        444,
        555
    ];
    const PRODUCT_CATEGORY_NAMES = [
        'Category1',
        'Category2',
        'Category3',
        'Category4',
        'Category5'
    ];
}
