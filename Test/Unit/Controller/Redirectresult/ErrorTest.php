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
use Sapient\Worldpay\Helper\CreditCardException;
use Sapient\Worldpay\Model\Recurring\Subscription\TransactionsFactory;
use \PHPUnit\Framework\TestCase;
use Sapient\Worldpay\Controller\Redirectresult\Error;

class ErrorTest extends TestCase {
    
    protected $errorObj;
    protected function setUp() {
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
        $helper = $this->getMockBuilder(CreditCardException::class)
                        ->disableOriginalConstructor()->getMock();
        $checkoutSession = $this->getMockBuilder(Session::class)
                        ->disableOriginalConstructor()->getMock();
        $wplogger = $this->getMockBuilder(WorldpayLogger::class)
                        ->disableOriginalConstructor()->getMock();

        $this->errorObj = new Error($context, $page, $orderservice, $wplogger,
                $subscriptionFactory, $transactionsFactory, $checkoutSession, $helper);
    }
    
    public function testExecute()
    {
        $this->assertInstanceOf(Error::class, $this->errorObj);
    }
    
}
