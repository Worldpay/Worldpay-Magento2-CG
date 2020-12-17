<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Test\Unit\Controller\Applepay;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Sapient\Worldpay\Logger\WorldpayLogger;
use Sapient\Worldpay\Model\Payment\Service;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use \PHPUnit\Framework\TestCase;
use Sapient\Worldpay\Controller\Applepay\Index;

class IndexTest extends TestCase
{

    protected $scopeConfig;
    protected $request;
    protected $indexObj;
    protected $objectManagerMock;

    protected function setUp()
    {
        $context = $this->getMockBuilder(Context::class)
                        ->disableOriginalConstructor()->getMock();
        $jsonFactory = $this->getMockBuilder(JsonFactory::class)
                        ->disableOriginalConstructor()->getMock();
        $wplogger = $this->getMockBuilder(WorldpayLogger::class)
                        ->disableOriginalConstructor()->getMock();
        $paymentservice = $this->getMockBuilder(Service::class)
                        ->disableOriginalConstructor()->getMock();
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
                        ->disableOriginalConstructor()->getMock();
        $this->request = $this->getMockBuilder(Http::class)
                        ->disableOriginalConstructor()->getMock();
        $this->objectManagerMock = $this->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)
                ->disableOriginalConstructor()->getMock();

        $this->indexObj = new Index($context, $jsonFactory, $wplogger, $paymentservice, $this->scopeConfig, $this->request);
    }

    public function testExecute()
    {
        $this->scopeConfig
                ->expects($this->any())
                ->method('getValue')
                ->withConsecutive(['worldpay/wallets_config/apple_pay_wallets_config/certification_key',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE], 
                        ['worldpay/wallets_config/apple_pay_wallets_config/certification_crt',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE], 
                        ['worldpay/wallets_config/apple_pay_wallets_config/certification_password', 
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE], 
                        ['worldpay/wallets_config/apple_pay_wallets_config/merchant_name', 
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE], 
                        ['worldpay/wallets_config/apple_pay_wallets_config/domain_name', 
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE])
                ->will($this->onConsecutiveCalls(
                    '/var/www/html/webroot-apple/publicis_ecom_live_merchant_identity.key.pem',
                    '/var/www/html/webroot-apple/publicis_ecom_live_merchant_identity.crt.pem',
                    '',
                    'merchant.com.publicissapient.ecom.live',
                    'wpgqa.wpmage.uk'
                ));
        $validation_url = $this->request
                ->expects($this->any())
                ->method('getParam')
                ->with('u')
                ->willReturn('getTotal');
        
        $valueMap = $this->getMockBuilder(\Magento\Checkout\Model\Cart::class)
                        ->disableOriginalConstructor()->getMock();

        $this->objectManagerMock->expects($this->any())
                ->method('get')->willReturn($valueMap);

        $this->assertInstanceOf(Index::class, $this->indexObj);
    }
}
