<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Test\Unit\Model\Config\Source;

use Sapient\Worldpay\Model\Config\Source\PaymentMethods;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use \PHPUnit\Framework\TestCase;

class PaymentMethodsTest extends TestCase
{

    /** @var PaymentMethods  */
    protected $model;

    protected function setUp()
    {
        $contextMock = $this->getMockBuilder(Context::class)
                        ->disableOriginalConstructor()->getMock();
        $registryMock = $this->getMockBuilder(Registry::class)
                        ->disableOriginalConstructor()->getMock();
        $configMock = $this->getMockBuilder(ScopeConfigInterface::class)
                        ->disableOriginalConstructor()->getMock();
        $cacheTypeListMock = $this->getMockBuilder(TypeListInterface::class)
                        ->disableOriginalConstructor()->getMock();
        $this->model = new PaymentMethods($contextMock, $registryMock, $configMock, $cacheTypeListMock);
    }

    public function testToOptionArray()
    {
        $expectedResult = [
            ['value' => 'AMEX', 'label' => __('American Express')],
            ['value' => 'VISA', 'label' => __('Visa')],
            ['value' => 'DISCOVER', 'label' => __('Discover')],
            ['value' => 'JCB', 'label' => __('Japanese Credit Bank')],
            ['value' => 'MASTERCARD', 'label' => __('Master Card')]
        ];
        $this->assertEquals($expectedResult, $this->model->toOptionArray());
    }
}
