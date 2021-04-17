<?php
//

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
use Sapient\Worldpay\Model\Request\AuthenticationService;
use \PHPUnit\Framework\TestCase;
use Sapient\Worldpay\Controller\Redirectresult\Cancel;

class CancelTest extends TestCase
{

    protected $cancelObj;

    protected function setUp(): void
    {
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
        $authenticatinservice = $this->getMockBuilder(AuthenticationService::class)
                        ->disableOriginalConstructor()->getMock();
        $wplogger = $this->getMockBuilder(WorldpayLogger::class)
                        ->disableOriginalConstructor()->getMock();
        $this->cancelObj = new Cancel(
            $context,
            $page,
            $orderservice,
            $checkoutservice,
            $paymentservice,
            $authenticatinservice,
            $wplogger
        );
    }

    public function testExecute()
    {
        $this->assertInstanceOf(Cancel::class, $this->cancelObj);
    }
}
