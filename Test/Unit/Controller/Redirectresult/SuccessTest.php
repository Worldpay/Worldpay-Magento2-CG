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
use \PHPUnit\Framework\TestCase;
use Sapient\Worldpay\Controller\Redirectresult\Success;

class SuccessTest extends TestCase
{

    protected $successObj;

    protected function setUp(): void
    {
        $context = $this->getMockBuilder(Context::class)
                        ->disableOriginalConstructor()->getMock();
        $page = $this->getMockBuilder(PageFactory::class)
                        ->disableOriginalConstructor()->getMock();
        $orderservice = $this->getMockBuilder(OrderService::class)
                        ->disableOriginalConstructor()->getMock();
        $wplogger = $this->getMockBuilder(WorldpayLogger::class)
                        ->disableOriginalConstructor()->getMock();
        $this->successObj = new Success($context, $page, $orderservice, $wplogger);
    }

    public function testExecute()
    {
        $this->assertInstanceOf(Success::class, $this->successObj);
    }
}
