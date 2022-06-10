<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Test\Unit\Controller\Redirectresult;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Sapient\Worldpay\Logger\WorldpayLogger;
use Sapient\Worldpay\Model\Checkout\Hpp\State;
use \PHPUnit\Framework\TestCase;
use Sapient\Worldpay\Controller\Redirectresult\Iframe;

class IframeTest extends TestCase
{
    /**
     * [$iframeObj description]
     * @var [type]
     */
    protected $iframeObj;
    /**
     * [setUp description]
     */
    protected function setUp()
    {
        $context = $this->getMockBuilder(Context::class)
                        ->disableOriginalConstructor()->getMock();
        $page = $this->getMockBuilder(PageFactory::class)
                        ->disableOriginalConstructor()->getMock();
        $hppstate = $this->getMockBuilder(State::class)
                        ->disableOriginalConstructor()->getMock();
        $wplogger = $this->getMockBuilder(WorldpayLogger::class)
                        ->disableOriginalConstructor()->getMock();

        $this->iframeObj = new Iframe($context, $page, $hppstate, $wplogger);
    }
    /**
     * [testExecute description]
     * @return [type] [description]
     */
    public function testExecute()
    {
        $this->assertInstanceOf(Iframe::class, $this->iframeObj);
    }
}
