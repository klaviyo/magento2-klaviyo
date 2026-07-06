<?php

namespace Klaviyo\Reclaim\Test\Unit\Block\Catalog\Product;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Test\Data\SampleExtension;
use Klaviyo\Reclaim\Test\Data\SampleProduct;
use Klaviyo\Reclaim\Block\Catalog\Product\ViewedProduct;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Helper\Data;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
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

    protected function setUp(): void
    {
        $storeMock = $this->createMock(\Magento\Store\Api\Data\StoreInterface::class);
        $storeMock->method('getWebsiteId')->willReturn(SampleExtension::WEBSITE_ID);
        $storeMock->method('getId')->willReturn(SampleExtension::STORE_ID);
        $storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $storeManagerMock->method('getStore')->willReturn($storeMock);

        $contextMock = $this->createMock(Context::class);
        $contextMock->method('getStoreManager')->willReturn($storeManagerMock);

        $scopeSettingMock = $this->createMock(ScopeSetting::class);
        $scopeSettingMock->method('getPublicApiKey')->willReturn(SampleExtension::PUBLIC_API_KEY);
        $scopeSettingMock->method('isEnabled')->willReturn(SampleExtension::IS_ENABLED);
        $scopeSettingMock->storeId = SampleExtension::STORE_ID;

        $dataHelperMock = $this->createMock(Data::class);
        $dataHelperMock->method('getExternalCatalogIdForEvent')
            ->with(SampleExtension::WEBSITE_ID, SampleExtension::STORE_ID)
            ->willReturn(SampleExtension::EXTERNAL_CATALOG_ID);

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

        // getProductCategories() now reads categories directly off the product
        // via getCategoryCollection()->addAttributeToSelect('name')->getColumnValues('name')
        // rather than looking each category up by id through a CategoryFactory.
        $categoryCollectionMock = $this->createMock(CategoryCollection::class);
        $categoryCollectionMock->method('addAttributeToSelect')
            ->with('name')
            ->willReturnSelf();
        $categoryCollectionMock->method('getColumnValues')
            ->with('name')
            ->willReturn(SampleProduct::PRODUCT_CATEGORY_NAMES);
        $productMock->method('getCategoryCollection')->willReturn($categoryCollectionMock);

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
            $imageMock,
            $dataHelperMock
        );
    }

    protected function tearDown(): void
    {
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
            'external_catalog_id' => SampleExtension::EXTERNAL_CATALOG_ID,
            'integration_key' => 'magento_two',
            'ProductID' => SampleProduct::PRODUCT_ID,
            'Name' => SampleProduct::PRODUCT_NAME,
            'SKU' => SampleProduct::PRODUCT_SKU,
            'URL' => SampleProduct::PRODUCT_URL,
            'Price' => number_format(SampleProduct::PRODUCT_PRICE, 2),
            'FinalPrice' => number_format(SampleProduct::PRODUCT_PRICE_FINAL, 2),
            'Categories' => SampleProduct::PRODUCT_CATEGORY_NAMES,
            'StoreId' => SampleExtension::STORE_ID,
            '$value' => str_replace(',', '', number_format(SampleProduct::PRODUCT_PRICE, 2)),
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
