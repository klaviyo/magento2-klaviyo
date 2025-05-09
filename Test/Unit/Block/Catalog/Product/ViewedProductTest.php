<?php

namespace Klaviyo\Reclaim\Test\Unit\Block\Catalog\Product;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Test\Data\SampleExtension;
use Klaviyo\Reclaim\Test\Data\SampleProduct;
use Klaviyo\Reclaim\Block\Catalog\Product\ViewedProduct;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Catalog\Helper\Image;
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
    protected $viewedProduct;

    /**
     * @var array
     */
    protected $categoryMocks;

    protected function setUp(): void
    {

        $contextMock = $this->createMock(Context::class);

        $scopeSettingMock = $this->createMock(ScopeSetting::class);
        $scopeSettingMock->method('getPublicApiKey')->willReturn(SampleExtension::PUBLIC_API_KEY);
        $scopeSettingMock->method('isEnabled')->willReturn(SampleExtension::IS_ENABLED);

        $productMock = $this->createMock(Product::class);
        $productMock->method('getId')->willReturn(SampleProduct::PRODUCT_ID);
        $productMock->method('getName')->willReturn(SampleProduct::PRODUCT_NAME);
        $productMock->method('getSku')->willReturn(SampleProduct::PRODUCT_SKU);
        $productMock->method('getProductUrl')->willReturn(SampleProduct::PRODUCT_URL);
        $productMock->method('getTypeId')->willReturn('simple');

        $priceInterfaceMock = $this->createMock(PriceInterface::class);
        $priceInterfaceMock->method('getValue')->willReturn(SampleProduct::PRODUCT_PRICE_FINAL);
        $priceInfoMock = $this->createMock(PriceInfo::class);
        $priceInfoMock->method('getPrice')
            ->with($this->equalTo('final_price'))
            ->willReturn($priceInterfaceMock);
        $productMock->method('getPrice')->willReturn(SampleProduct::PRODUCT_PRICE);
        $productMock->method('getPriceInfo')->willReturn($priceInfoMock);

        $categoryMock0 = $this->createMock(Category::class);
        $categoryMock0->method('getName')->willReturn(SampleProduct::PRODUCT_CATEGORY_NAMES[0]);
        $this->categoryMocks[0] = $categoryMock0;

        $categoryMock1 = $this->createMock(Category::class);
        $categoryMock1->method('getName')->willReturn(SampleProduct::PRODUCT_CATEGORY_NAMES[1]);
        $this->categoryMocks[1] = $categoryMock1;

        $categoryMock2 = $this->createMock(Category::class);
        $categoryMock2->method('getName')->willReturn(SampleProduct::PRODUCT_CATEGORY_NAMES[2]);
        $this->categoryMocks[2] = $categoryMock2;

        $categoryMock3 = $this->createMock(Category::class);
        $categoryMock3->method('getName')->willReturn(SampleProduct::PRODUCT_CATEGORY_NAMES[3]);
        $this->categoryMocks[3] = $categoryMock3;

        $categoryMock4 = $this->createMock(Category::class);
        $categoryMock4->method('getName')->willReturn(SampleProduct::PRODUCT_CATEGORY_NAMES[4]);
        $this->categoryMocks[4] = $categoryMock4;

        $categoryCollectionMock = $this->createMock(CategoryCollection::class);
        $categoryCollectionMock->method('load')->will($this->returnCallback(
            function ($id) {
                switch ($id) {
                    case SampleProduct::PRODUCT_CATEGORY_IDS[0];
                        return $this->categoryMocks[0];
                        break;
                    case SampleProduct::PRODUCT_CATEGORY_IDS[1];
                        return $this->categoryMocks[1];
                        break;
                    case SampleProduct::PRODUCT_CATEGORY_IDS[2];
                        return $this->categoryMocks[2];
                        break;
                    case SampleProduct::PRODUCT_CATEGORY_IDS[3];
                        return $this->categoryMocks[3];
                        break;
                    case SampleProduct::PRODUCT_CATEGORY_IDS[4];
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

        $productMock->method('getCategoryIds')->willReturn(SampleProduct::PRODUCT_CATEGORY_IDS);

        $registryMock = $this->createMock(Registry::class);
        $registryMock->method('registry')->willReturn($productMock);

        $imageMock = $this->createMock(Image::class);
        $imageMock->method('init')
            ->with($this->isInstanceOf(Product::class), $this->equalTo('product_base_image'))
            ->willReturn($imageMock);
        $imageMock->method('getUrl')->willReturn(SampleProduct::PRODUCT_IMAGE_URL);

        $this->viewedProduct = new ViewedProduct(
            $contextMock,
            $scopeSettingMock,
            $registryMock,
            $categoryFactoryMock,
            $imageMock
        );
    }

    protected function tearDown(): void
    {
        $this->categoryMocks = [];
    }

    public function testViewedProductInstance()
    {
        $this->assertInstanceOf(ViewedProduct::class, $this->viewedProduct);
    }

    public function testGetPublicApiKey()
    {
        $this->assertSame(SampleExtension::PUBLIC_API_KEY, $this->viewedProduct->getPublicApiKey());
    }

    public function testIsKlaviyoEnabled()
    {
        $this->assertSame(SampleExtension::IS_ENABLED, $this->viewedProduct->isKlaviyoEnabled());
    }

    public function testGetProduct()
    {
        $this->assertInstanceOf(Product::class, $this->viewedProduct->getProduct());
    }

    public function testGetProductCategories()
    {
        $this->assertSame(SampleProduct::PRODUCT_CATEGORY_NAMES, $this->viewedProduct->getProductCategories());
    }

    public function testGetProductCategoriesAsJson()
    {
        $this->assertSame(json_encode(SampleProduct::PRODUCT_CATEGORY_NAMES), $this->viewedProduct->getProductCategoriesAsJson());
    }

    public function testGetPrice()
    {
        $this->assertSame(number_format(SampleProduct::PRODUCT_PRICE, 2), $this->viewedProduct->getPrice());
    }

    public function testGetFinalPrice()
    {
        $this->assertSame(number_format(SampleProduct::PRODUCT_PRICE_FINAL, 2), $this->viewedProduct->getFinalPrice());
    }

    public function testGetProductImage()
    {
        $this->assertSame(SampleProduct::PRODUCT_IMAGE_URL, $this->viewedProduct->getProductImage());
    }

    public function testGetViewedProductJson()
    {
        $expectedResponse = [
            'ProductID' => SampleProduct::PRODUCT_ID,
            'Name' => SampleProduct::PRODUCT_NAME,
            'SKU' => SampleProduct::PRODUCT_SKU,
            'URL' => SampleProduct::PRODUCT_URL,
            'Price' => number_format(SampleProduct::PRODUCT_PRICE, 2),
            'FinalPrice' => number_format(SampleProduct::PRODUCT_PRICE_FINAL, 2),
            'Categories' => SampleProduct::PRODUCT_CATEGORY_NAMES,
            'ImageURL' => SampleProduct::PRODUCT_IMAGE_URL
        ];
        $expectedResponse = json_encode($expectedResponse);
        $this->assertSame($expectedResponse, $this->viewedProduct->getViewedProductJson());
    }

    public function getViewedItemJson()
    {
        $expectedResponse = [
            'Title' => SampleProduct::PRODUCT_NAME,
            'ItemId' => SampleProduct::PRODUCT_ID,
            'Url' => SampleProduct::PRODUCT_URL,
            'Categories' => SampleProduct::PRODUCT_CATEGORY_NAMES,
            'Metadata' => [
                    'Price' => number_format(SampleProduct::PRODUCT_PRICE, 2)
            ]
        ];
        $expectedResponse = json_encode($expectedResponse);
        $this->assertSame($expectedResponse, $this->viewedProduct->getViewedItemJson());
    }
}
