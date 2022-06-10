<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Test\Unit\Controller\Hostedpaymentpage;

use \PHPUnit\Framework\TestCase;
use Sapient\Worldpay\Controller\Hostedpaymentpage\Pay;
use Magento\Framework\View\Result\PageFactory;
use Sapient\Worldpay\Helper\Data;
use Magento\Framework\App\Action\Context;
use Sapient\Worldpay\Model\Checkout\Hpp\State;
use Sapient\Worldpay\Logger\WorldpayLogger;
use Magento\Framework\ObjectManagerInterface;
use \Magento\Framework\View\Result\Page;

class PayTest extends TestCase
{

    /**
     * [$payObj description]
     * @var [type]
     */
    protected $payObj;
    /**
     * [$context description]
     * @var [type]
     */
    protected $context;
    /**
     * [$pageFactory description]
     * @var [type]
     */
    protected $pageFactory;
    /**
     * [$hppstate description]
     * @var [type]
     */
    protected $hppstate;
    /**
     * [$dataHelper description]
     * @var [type]
     */
    protected $dataHelper;
    /**
     * [setUp description]
     */
    protected function setUp()
    {
        $this->context = $this->getMockBuilder(Context::class)
                ->disableOriginalConstructor()
                ->getMock();
        $this->pageFactory = $this->getMockBuilder(PageFactory::class)
                ->disableOriginalConstructor()
                ->getMock();
        $wplogger = $this->getMockBuilder(WorldpayLogger::class)
                        ->disableOriginalConstructor()->getMock();
        $this->dataHelper = $this->getMockBuilder(Data::class)
                ->disableOriginalConstructor()
                ->getMock();
        $this->hppstate = $this->getMockBuilder(State::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->payObj = new Pay($this->context, $this->pageFactory, $this->hppstate, $this->dataHelper, $wplogger);
    }
    /**
     * [testExecuteIsIframe description]
     * @return [type] [description]
     */
    public function testExecuteIsIframe()
    {
        $this->dataHelper->expects($this->any())
                ->method('isIframeIntegration')
                ->willReturn(true);

        $ObjectManager = $this->getMockBuilder(ObjectManagerInterface::class)
                ->disableOriginalConstructor()
                ->getMock();

        $page = $this->getMockBuilder(Page::class)
                ->disableOriginalConstructor()
                ->getMock();

        $ObjectManager->expects($this->any())
                ->method('create')
                ->with($page, [])
                ->willReturn($page);

        $this->pageFactory->expects($this->any())
                ->method('create')
                ->willReturn($page);
        $this->assertInstanceOf(Pay::class, $this->payObj);
    }
}
