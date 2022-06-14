<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Test\Unit\Controller\Samsungpay;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Sapient\Worldpay\Logger\WorldpayLogger;
use Sapient\Worldpay\Model\Payment\Service;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use \PHPUnit\Framework\TestCase;
use Sapient\Worldpay\Controller\Samsungpay\Index;
use Magento\Quote\Model\QuoteFactory;
use \Magento\Store\Model\StoreManagerInterface;

class IndexTest extends TestCase
{

    /**
     * Core store config
     *
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var RequestInterface
     */
    protected $request;
    /**
     * @var /Sapient\Worldpay\Controller\Applepay\Index
     */
    protected $indexObj;
    /**
     * @var ObjectManagerInterface|MockObject
     */
    protected $objectManagerMock;

    protected function setUp(): void
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
        $this->quoteFactory = $this->getMockBuilder(QuoteFactory::class)
                        ->disableOriginalConstructor()->getMock();
        $this->_storeManager = $this->getMockBuilder(StoreManagerInterface::class)
                        ->disableOriginalConstructor()->getMock();

        $this->indexObj = new Index(
            $context,
            $jsonFactory,
            $wplogger,
            $paymentservice,
            $this->scopeConfig,
            $this->request,
            $this->quoteFactory,
            $this->_storeManager
        );
    }

    public function testExecute()
    {
        $this->scopeConfig
                ->expects($this->any())
                ->method('getValue')
                ->withConsecutive(
                    ['worldpay/wallets_config/samsung_pay_wallets_config/service_id',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE],
                    ['worldpay/wallets_config/samsung_pay_wallets_config/samsung_merchant_shop_name',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE],
                    ['worldpay/wallets_config/samsung_pay_wallets_config/samsung_merchant_shop_url',
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE],
                    ['worldpay/general_config/environment_mode',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE]
                )
                ->willReturn('SamsungRespone');
        $this->assertInstanceOf(Index::class, $this->indexObj);
    }
}
