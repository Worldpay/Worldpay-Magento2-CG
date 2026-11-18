<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Test\Unit\Controller\Hostedpaymentpage;

use \PHPUnit\Framework\TestCase;
use Sapient\Worldpay\Controller\Hostedpaymentpage\Pay;
use Magento\Framework\View\Result\PageFactory;
use Sapient\Worldpay\Helper\Data;
use Magento\Framework\App\Action\Context;
use Sapient\Worldpay\Model\Checkout\Hpp\State;
use Sapient\Worldpay\Logger\WorldpayLogger;
use \Magento\Framework\View\Result\Page;

class PayTest extends TestCase
{

    /**
     *
     * @var Pay
     */
    protected $payObj;
    /**
     * @var Context
     */
    protected $context;
    /**
     * @var Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;
    /**
     * @var State
     */
    protected $hppstate;
    /**
     * @var DataHelper
     */
    protected $dataHelper;

    protected function setUp(): void
    {
        $this->context = $this->createStub(Context::class);
        $this->pageFactory = $this->createStub(PageFactory::class);
        $wplogger = $this->createStub(WorldpayLogger::class);
        $this->dataHelper = $this->createStub(Data::class);
        $this->hppstate = $this->createStub(State::class);

        $this->payObj = new Pay($this->context, $this->pageFactory, $this->hppstate, $this->dataHelper, $wplogger);
    }

    public function testExecuteIsIframe()
    {
        $this->dataHelper->method('isIframeIntegration')
                ->willReturn(true);

        $page = $this->createStub(Page::class);

        $this->pageFactory->method('create')
                ->willReturn($page);
        $this->assertInstanceOf(Pay::class, $this->payObj);
    }
}
