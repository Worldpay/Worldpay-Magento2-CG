<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Test\Unit\Model\Config\Source;

use Sapient\Worldpay\Model\Config\Source\PaymentMethodsApm;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use \PHPUnit\Framework\TestCase;

class PaymentMethodsApmTest extends TestCase
{

    /** @var PaymentMethodsApm  */
    protected $model;

    protected function setUp(): void
    {
        $contextMock = $this->getMockBuilder(Context::class)
                        ->disableOriginalConstructor()->getMock();
        $registryMock = $this->getMockBuilder(Registry::class)
                        ->disableOriginalConstructor()->getMock();
        $configMock = $this->getMockBuilder(ScopeConfigInterface::class)
                        ->disableOriginalConstructor()->getMock();
        $cacheTypeListMock = $this->getMockBuilder(TypeListInterface::class)
                        ->disableOriginalConstructor()->getMock();
        $this->model = new PaymentMethodsApm($contextMock, $registryMock, $configMock, $cacheTypeListMock);
    }

    public function testToOptionArray()
    {
        $expectedResult = [
            ['value' => 'CHINAUNIONPAY-SSL', 'label' => __('Union Pay')],
            ['value' => 'IDEAL-SSL', 'label' => __('IDEAL')],
           // ['value' => 'YANDEXMONEY-SSL', 'label' => __('Yandex.Money')],
            ['value' => 'PAYPAL-EXPRESS', 'label' => __('PayPal Express')],
            ['value' => 'PAYPAL-SSL', 'label' => __('PayPal SSL')],
            ['value' => 'SOFORT-SSL', 'label' => __('SoFort EU')],
            ['value' => 'GIROPAY-SSL', 'label' => __('GiroPay')],
            //['value' => 'BOLETO-SSL', 'label' => __('Boleto Bancairo')],
            ['value' => 'ALIPAY-SSL', 'label' => __('AliPay')],
            ['value' => 'SEPA_DIRECT_DEBIT-SSL', 'label' =>
                __('SEPA (One off transactions)')], ['value' => 'KLARNA-SSL', 'label' => __('Klarna (Redirect)')],
            ['value' => 'PRZELEWY-SSL', 'label' => __('P24')],
            ['value' => 'MISTERCASH-SSL', 'label' => __('Mistercash/Bancontact')],
            ['value' => 'ACH_DIRECT_DEBIT-SSL', 'label' => __('ACH Pay')],
            ['value' => 'OPENBANKING-SSL', 'label' => __('Pay By Bank')],
        ];
        $this->assertEquals($expectedResult, $this->model->toOptionArray());
    }
}
