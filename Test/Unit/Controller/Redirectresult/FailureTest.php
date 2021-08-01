<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Test\Unit\Controller\Redirectresult;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Sapient\Worldpay\Logger\WorldpayLogger;
use Sapient\Worldpay\Model\Order\Service as OrderService;
use Sapient\Worldpay\Model\Recurring\SubscriptionFactory;
use Magento\Checkout\Model\Session;
use Sapient\Worldpay\Model\Recurring\Subscription\TransactionsFactory;
use \PHPUnit\Framework\TestCase;
use Sapient\Worldpay\Controller\Redirectresult\Failure;

class FailureTest extends TestCase
{
    
    protected $failureObj;
    protected function setUp(): void
    {
        $context = $this->getMockBuilder(Context::class)
                        ->disableOriginalConstructor()->getMock();
        $page = $this->getMockBuilder(PageFactory::class)
                        ->disableOriginalConstructor()->getMock();
        $orderservice = $this->getMockBuilder(OrderService::class)
                        ->disableOriginalConstructor()->getMock();
        $subscriptionFactory = $this->getMockBuilder(SubscriptionFactory::class)
                        ->disableOriginalConstructor()->getMock();
        $transactionsFactory = $this->getMockBuilder(TransactionsFactory::class)
                        ->disableOriginalConstructor()->getMock();
        $checkoutSession = $this->getMockBuilder(Session::class)
                        ->disableOriginalConstructor()->getMock();
        $wplogger = $this->getMockBuilder(WorldpayLogger::class)
                        ->disableOriginalConstructor()->getMock();

        $this->failureObj = new Failure(
            $context,
            $page,
            $orderservice,
            $wplogger,
            $subscriptionFactory,
            $transactionsFactory,
            $checkoutSession
        );
    }
    
    public function testExecute()
    {
        $this->assertInstanceOf(Failure::class, $this->failureObj);
    }
}
