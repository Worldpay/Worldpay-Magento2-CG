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
    use \Sapient\Worldpay\Test\Unit\ObjectFactory;
    /** @var QuoteFactory */
    protected $quoteFactory;

    /** @var StoreManagerInterface */
    protected $_storeManager;

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
        $context = $this->createStub(Context::class);
        $jsonFactory = $this->createStub(JsonFactory::class);
        $wplogger = $this->createStub(WorldpayLogger::class);
        $paymentservice = $this->createStub(Service::class);
        $this->scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $this->request = $this->createStub(Http::class);
        $this->quoteFactory = $this->createStub(QuoteFactory::class);
        $this->_storeManager = $this->createStub(StoreManagerInterface::class);

        $this->indexObj = $this->createObject(
            Index::class,
            [
                'context' => $context,
                'resultJsonFactory' => $jsonFactory,
                'wplogger' => $wplogger,
                'paymentservice' => $paymentservice,
                'scopeConfig' => $this->scopeConfig,
                'request' => $this->request,
                'quoteFactory' => $this->quoteFactory,
                'storeManager' => $this->_storeManager,
            ]
        );
    }

    public function testExecute()
    {
        $this->assertInstanceOf(Index::class, $this->indexObj);
    }
}
