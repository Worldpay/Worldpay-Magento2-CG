<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Test\Unit\Controller\Wallets;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Sapient\Worldpay\Logger\WorldpayLogger;
use \PHPUnit\Framework\TestCase;
use Sapient\Worldpay\Controller\Wallets\Success;

class SuccessTest extends TestCase
{
    
    /**
     * @var Success
     */
    protected $successObj;
    protected function setUp(): void
    {
        $context = $this->getMockBuilder(Context::class)
                        ->disableOriginalConstructor()->getMock();
        
        $wplogger = $this->getMockBuilder(WorldpayLogger::class)
                        ->disableOriginalConstructor()->getMock();
        
        $page = $this->getMockBuilder(PageFactory::class)
                        ->disableOriginalConstructor()->getMock();

        $this->successObj = new Success($context, $wplogger, $page);
    }
    
    public function testExecute()
    {
        $this->assertInstanceOf(Success::class, $this->successObj);
    }
}
