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
    use \Sapient\Worldpay\Test\Unit\ObjectFactory;

    /**
     *
     * @var Cancel
     */
    protected $cancelObj;

    protected function setUp(): void
    {
        $context = $this->createStub(Context::class);
        $page = $this->createStub(PageFactory::class);
        $orderservice = $this->createStub(OrderService::class);
        $checkoutservice = $this->createStub(CheckoutService::class);
        $paymentservice = $this->createStub(PaymentService::class);
        $authenticatinservice = $this->createStub(AuthenticationService::class);
        $wplogger = $this->createStub(WorldpayLogger::class);
        $this->cancelObj = $this->createObject(
            Cancel::class,
            [
                'context' => $context,
                'pageFactory' => $page,
                'orderservice' => $orderservice,
                'checkoutservice' => $checkoutservice,
                'paymentservice' => $paymentservice,
                'authenticatinservice' => $authenticatinservice,
                'wplogger' => $wplogger,
            ]
        );
    }

    public function testExecute()
    {
        $this->assertInstanceOf(Cancel::class, $this->cancelObj);
    }
}
