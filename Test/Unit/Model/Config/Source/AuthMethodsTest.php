<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Test\Unit\Model\Config\Source;

use Sapient\Worldpay\Model\Config\Source\AuthMethods;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use \PHPUnit\Framework\TestCase;

class AuthMethodsTest extends TestCase
{

    /** @var AuthMethods  */
    protected $model;

    protected function setUp(): void
    {
        $contextMock = $this->createStub(Context::class);
        $registryMock = $this->createStub(Registry::class);
        $configMock = $this->createStub(ScopeConfigInterface::class);
        $cacheTypeListMock = $this->createStub(TypeListInterface::class);
        $this->model = new AuthMethods($contextMock, $registryMock, $configMock, $cacheTypeListMock);
    }

    public function testToOptionArray()
    {
        $expectedResult = [
            ['value' => 'PAN_ONLY', 'label' => __('Pan Only')],
            ['value' => 'CRYPTOGRAM_3DS', 'label' => __('Cryptogram 3ds')]
        ];
        $this->assertEquals($expectedResult, $this->model->toOptionArray());
    }
}
