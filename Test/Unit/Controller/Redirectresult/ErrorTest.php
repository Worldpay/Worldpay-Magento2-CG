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

class ErrorTest extends TestCase
{
    use \Sapient\Worldpay\Test\Unit\ObjectFactory;
    
    /**
     * @var Error
     */
    protected $errorObj;
    protected function setUp(): void
    {
        $context = $this->createStub(Context::class);
        $page = $this->createStub(PageFactory::class);
        $orderservice = $this->createStub(OrderService::class);
        $subscriptionFactory = $this->createStub(SubscriptionFactory::class);
        $transactionsFactory = $this->createStub(TransactionsFactory::class);
        $helper = $this->createStub(CreditCardException::class);
        $checkoutSession = $this->createStub(Session::class);
        $wplogger = $this->createStub(WorldpayLogger::class);

        $this->errorObj = $this->createObject(
            Error::class,
            [
                'context' => $context,
                'pageFactory' => $page,
                'orderservice' => $orderservice,
                'wplogger' => $wplogger,
                'subscriptionFactory' => $subscriptionFactory,
                'transactionsFactory' => $transactionsFactory,
                'checkoutSession' => $checkoutSession,
                'helper' => $helper,
            ]
        );
    }
    
    public function testExecute()
    {
        $this->assertInstanceOf(Error::class, $this->errorObj);
    }
}
