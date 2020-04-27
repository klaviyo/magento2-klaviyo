<?php

namespace Klaviyo\Reclaim\Test\Unit\Block\Catalog\Product;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Block\Catalog\Product\ViewedProduct;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class ViewedProductTest extends TestCase
{
    /**
     * @var ViewedProduct
     */
    protected $object;

    protected function setUp()
    {

        $contextMock = $this->createMock(Context::class);

        $scopeSettingMock = $this->createMock(ScopeSetting::class);
        $scopeSettingMock->method('getPublicApiKey')->willReturn('QWEasd');
        $scopeSettingMock->method('isEnabled')->willReturn(true);

        //$catalogProductMock = $this->createMock();

        $registryMock = $this->createMock(Registry::class);
        //$registryMock->method('registry')->willReturn();

        $categoryMock = $this->getMockBuilder(CategoryFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $categoriesMock = ['TestCategory'];
        //$categoryMock->method('create')->willReturn($categoriesMock);

        $imageMock = $this->createMock(Image::class);

        $this->object = new ViewedProduct(
            $contextMock,
            $scopeSettingMock,
            $registryMock,
            $categoryMock,
            $imageMock
        );
    }

    public function testViewedProductInstance()
    {
        $this->assertInstanceOf(ViewedProduct::class, $this->object);
    }

    public function testGetPublicApiKey()
    {
        $this->assertSame('QWEasd', $this->object->getPublicApiKey());
    }

    public function testIsKlaviyoEnabled()
    {
        $this->assertSame(true, $this->object->isKlaviyoEnabled());
    }

    public function getProduct()
    {

    }


    public function getProductCategories()
    {

    }


    public function getProductCategoriesAsJson()
    {

    }


    public function getPrice()
    {

    }

    public function getFinalPrice()
    {

    }

    public function getProductImage()
    {

    }

    protected function _toHtml()
    {

    }


    public function getViewedProductJson()
    {

    }

    public function getViewedItemJson()
    {

    }
}