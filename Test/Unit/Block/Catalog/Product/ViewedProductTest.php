<?php

namespace Klaviyo\Reclaim\Test\Unit\Block\Catalog\Product;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Block\Catalog\Product\ViewedProduct;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Catalog\Helper\Image;
//use Magento\Catalog\Model\ResourceModel\Category\Collection\Factory as CategoryFactory;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\PriceInfo\Base as PriceInfo;
use Magento\Framework\Pricing\Price\PriceInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class ViewedProductTest extends TestCase
{
    /**
     * @var ViewedProduct
     */
    protected $object;

    /**
     * @var array
     */
    protected $categoryMocks;

    const IS_ENABLED = TRUE;
    const PUBLIC_API_KEY = 'QWEasd';
    const PRODUCT_ID = 1234;
    const PRODUCT_NAME = 'Test Product';
    const PRODUCT_SKU = 'TEST_PRODUCT_1234';
    const PRODUCT_URL = 'https://www.example.com/test-product-1234';
    const PRODUCT_IMAGE_URL = 'https://www.example.com/media/catalog/product/cache/image/698x900/asdf/placeholder/default/base.jpg';
    const PRODUCT_PRICE = 543.21;
    const PRODUCT_PRICE_FINAL = 123.45;
    const PRODUCT_CATEGORY_IDS = array(111, 222, 333, 444, 555);
    const PRODUCT_CATEGORY_NAMES = [
        "Category1",
        "Category2",
        "Category3",
        "Category4",
        "Category5"
    ];


    protected function setUp()
    {

        $contextMock = $this->createMock(Context::class);

        $scopeSettingMock = $this->createMock(ScopeSetting::class);
        $scopeSettingMock->method('getPublicApiKey')->willReturn(self::PUBLIC_API_KEY);
        $scopeSettingMock->method('isEnabled')->willReturn(self::IS_ENABLED);

        $productMock = $this->createMock(Product::class);
        $productMock->method('getId')->willReturn(self::PRODUCT_ID);
        $productMock->method('getName')->willReturn(self::PRODUCT_NAME);
        $productMock->method('getSku')->willReturn(self::PRODUCT_SKU);
        $productMock->method('getProductUrl')->willReturn(self::PRODUCT_URL);
        $productMock->method('getTypeId')->willReturn('simple');

        $priceInterfaceMock = $this->createMock(PriceInterface::class);
        $priceInterfaceMock->method('getValue')->willReturn(self::PRODUCT_PRICE_FINAL);
        $priceInfoMock = $this->createMock(PriceInfo::class);
        $priceInfoMock->method('getPrice')
            ->with($this->equalTo('final_price'))
            ->willReturn($priceInterfaceMock);
        $productMock->method('getPrice')->willReturn(self::PRODUCT_PRICE);
        $productMock->method('getPriceInfo')->willReturn($priceInfoMock);

        $categoryMock0 = $this->createMock(Category::class);
        $categoryMock0->method('getName')->willReturn(self::PRODUCT_CATEGORY_NAMES[0]);
        $this->categoryMocks[0] = $categoryMock0;

        $categoryMock1 = $this->createMock(Category::class);
        $categoryMock1->method('getName')->willReturn(self::PRODUCT_CATEGORY_NAMES[1]);
        $this->categoryMocks[1] = $categoryMock1;

        $categoryMock2 = $this->createMock(Category::class);
        $categoryMock2->method('getName')->willReturn(self::PRODUCT_CATEGORY_NAMES[2]);
        $this->categoryMocks[2] = $categoryMock2;

        $categoryMock3 = $this->createMock(Category::class);
        $categoryMock3->method('getName')->willReturn(self::PRODUCT_CATEGORY_NAMES[3]);
        $this->categoryMocks[3] = $categoryMock3;

        $categoryMock4 = $this->createMock(Category::class);
        $categoryMock4->method('getName')->willReturn(self::PRODUCT_CATEGORY_NAMES[4]);
        $this->categoryMocks[4] = $categoryMock4;

        $categoryCollectionMock = $this->createMock(CategoryCollection::class);
        $categoryCollectionMock->method('load')->will($this->returnCallback(
            function ($id) {
                switch ($id) {
                    case self::PRODUCT_CATEGORY_IDS[0];
                        return $this->categoryMocks[0];
                        break;
                    case self::PRODUCT_CATEGORY_IDS[1];
                        return $this->categoryMocks[1];
                        break;
                    case self::PRODUCT_CATEGORY_IDS[2];
                        return $this->categoryMocks[2];
                        break;
                    case self::PRODUCT_CATEGORY_IDS[3];
                        return $this->categoryMocks[3];
                        break;
                    case self::PRODUCT_CATEGORY_IDS[4];
                        return $this->categoryMocks[4];
                        break;
                }
            }
        ));
        $categoryFactoryMock = $this->getMockBuilder(CategoryFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $categoryFactoryMock->method('create')->willReturn($categoryCollectionMock);

        $productMock->method('getCategoryIds')->willReturn(self::PRODUCT_CATEGORY_IDS);

        $registryMock = $this->createMock(Registry::class);
        $registryMock->method('registry')->willReturn($productMock);

        $imageMock = $this->createMock(Image::class);
        $imageMock->method('init')
            ->with($this->isInstanceOf(Product::class),$this->equalTo('product_base_image'))
            ->willReturn($imageMock);
        $imageMock->method('getUrl')->willReturn(self::PRODUCT_IMAGE_URL);

        $this->object = new ViewedProduct(
            $contextMock,
            $scopeSettingMock,
            $registryMock,
            $categoryFactoryMock,
            $imageMock
        );
    }

    protected function tearDown()
    {
        $this->categoryMocks = array();
    }

    public function testViewedProductInstance()
    {
        $this->assertInstanceOf(ViewedProduct::class, $this->object);
    }

    public function testGetPublicApiKey()
    {
        $this->assertSame(self::PUBLIC_API_KEY, $this->object->getPublicApiKey());
    }

    public function testIsKlaviyoEnabled()
    {
        $this->assertSame(self::IS_ENABLED, $this->object->isKlaviyoEnabled());
    }

    public function testGetProduct()
    {
        $this->assertInstanceOf(Product::class, $this->object->getProduct());
    }

    public function testGetProductCategories()
    {
        $this->assertSame(self::PRODUCT_CATEGORY_NAMES, $this->object->getProductCategories());
    }

    public function testGetProductCategoriesAsJson()
    {
        $this->assertSame(json_encode(self::PRODUCT_CATEGORY_NAMES), $this->object->getProductCategoriesAsJson());
    }

    public function testGetPrice()
    {
        $this->assertSame(number_format(self::PRODUCT_PRICE, 2), $this->object->getPrice());
    }

    public function testGetFinalPrice()
    {
        $this->assertSame(number_format(self::PRODUCT_PRICE_FINAL, 2), $this->object->getFinalPrice());
    }

    public function testGetProductImage()
    {
        $this->assertSame(self::PRODUCT_IMAGE_URL, $this->object->getProductImage());
    }

    public function testGetViewedProductJson()
    {
        $expectedResponse = [
            'ProductID' => self::PRODUCT_ID,
            'Name' => self::PRODUCT_NAME,
            'SKU' => self::PRODUCT_SKU,
            'URL' => self::PRODUCT_URL,
            'Price' => number_format(self::PRODUCT_PRICE, 2),
            'FinalPrice' => number_format(self::PRODUCT_PRICE_FINAL, 2),
            'Categories' => self::PRODUCT_CATEGORY_NAMES,
            'ImageURL' => self::PRODUCT_IMAGE_URL
        ];
        $expectedResponse = json_encode($expectedResponse);
        $this->assertSame($expectedResponse, $this->object->getViewedProductJson());
    }

    public function getViewedItemJson()
    {
        $expectedResponse = [
            'Title' => self::PRODUCT_NAME,
            'ItemId' => self::PRODUCT_ID,
            'Url' => self::PRODUCT_URL,
            'Categories' => self::PRODUCT_CATEGORY_NAMES,
            'Metadata' => array(
                    'Price' => number_format(self::PRODUCT_PRICE, 2)
            )
        ];
        $expectedResponse = json_encode($expectedResponse);
        $this->assertSame($expectedResponse, $this->object->getViewedItemJson());
    }
}