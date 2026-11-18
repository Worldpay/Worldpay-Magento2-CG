<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Test\Unit\Model\Config\Source;

use Sapient\Worldpay\Model\Config\Source\ACHAccountTypes;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use \PHPUnit\Framework\TestCase;

class ACHAccountTypesTest extends TestCase
{

    /** @var ACHAccountTypes  */
    protected $model;

    protected function setUp(): void
    {
        $contextMock = $this->createStub(Context::class);
        $registryMock = $this->createStub(Registry::class);
        $configMock = $this->createStub(ScopeConfigInterface::class);
        $cacheTypeListMock = $this->createStub(TypeListInterface::class);
        $this->model = new ACHAccountTypes($contextMock, $registryMock, $configMock, $cacheTypeListMock);
    }

    public function testToOptionArray()
    {
        $expectedResult = [
            ['value' => 'Checking', 'label' => __('Checking')],
            ['value' => 'Savings', 'label' => __('Savings')],
            ['value' => 'Corporate', 'label' => __('Corporate')],
            ['value' => 'Corp Savings', 'label' => __('Corp Savings')],
        ];
        $this->assertEquals($expectedResult, $this->model->toOptionArray());
    }
}
