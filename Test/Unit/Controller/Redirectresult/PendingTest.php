<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Test\Unit\Controller\Redirectresult;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Sapient\Worldpay\Logger\WorldpayLogger;
use Sapient\Worldpay\Model\Order\Service as OrderService;
use Sapient\Worldpay\Model\Checkout\Service as CheckoutService;
use Sapient\Worldpay\Model\Payment\Service as PaymentService;
use \PHPUnit\Framework\TestCase;
use Sapient\Worldpay\Controller\Redirectresult\Pending;

class PendingTest extends TestCase {
    
    protected $pendingObj;
    protected function setUp() {
        $context = $this->getMockBuilder(Context::class)
                        ->disableOriginalConstructor()->getMock();
        $page = $this->getMockBuilder(PageFactory::class)
                        ->disableOriginalConstructor()->getMock();
        $orderservice = $this->getMockBuilder(OrderService::class)
                        ->disableOriginalConstructor()->getMock();
        $checkoutservice = $this->getMockBuilder(CheckoutService::class)
                        ->disableOriginalConstructor()->getMock();
        $paymentservice = $this->getMockBuilder(PaymentService::class)
                        ->disableOriginalConstructor()->getMock();
        $wplogger = $this->getMockBuilder(WorldpayLogger::class)
                        ->disableOriginalConstructor()->getMock();

        $this->pendingObj = new Pending($context, $page, $orderservice, $checkoutservice,
                $paymentservice, $wplogger);
    }
    
    public function testExecute()
    {
        $this->assertInstanceOf(Pending::class, $this->pendingObj);
    }
    
}
