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
    use \Sapient\Worldpay\Test\Unit\ObjectFactory;

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
        $this->objectManagerMock = $this->createStub(\Magento\Framework\ObjectManagerInterface::class);
        $this->indexObj = $this->createObject(
            Index::class,
            [
                'context' => $context,
                'resultJsonFactory' => $jsonFactory,
                'wplogger' => $wplogger,
                'paymentservice' => $paymentservice,
                'scopeConfig' => $this->scopeConfig,
                'request' => $this->request,
            ]
        );
    }

    public function testExecute()
    {
        $this->assertInstanceOf(Index::class, $this->indexObj);
    }
}
