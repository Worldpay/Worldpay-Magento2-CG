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
    use \Sapient\Worldpay\Test\Unit\ObjectFactory;

    /**
     * @var Success
     */
    protected $successObj;

    protected function setUp(): void
    {
        $context = $this->createStub(Context::class);
        $page = $this->createStub(PageFactory::class);
        $orderservice = $this->createStub(OrderService::class);
        $wplogger = $this->createStub(WorldpayLogger::class);
        $this->successObj = $this->createObject(
            Success::class,
            [
                'context' => $context,
                'pageFactory' => $page,
                'orderservice' => $orderservice,
                'wplogger' => $wplogger,
            ]
        );
    }

    public function testExecute()
    {
        $this->assertInstanceOf(Success::class, $this->successObj);
    }
}
