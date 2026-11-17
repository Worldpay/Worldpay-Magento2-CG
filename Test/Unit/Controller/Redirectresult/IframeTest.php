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
    use \Sapient\Worldpay\Test\Unit\ObjectFactory;
    
    /**
     * @var Iframe
     */
    protected $iframeObj;
    protected function setUp(): void
    {
        $context = $this->createStub(Context::class);
        $page = $this->createStub(PageFactory::class);
        $hppstate = $this->createStub(State::class);
        $wplogger = $this->createStub(WorldpayLogger::class);

        $this->iframeObj = $this->createObject(
            Iframe::class,
            [
                'context' => $context,
                'pageFactory' => $page,
                'hppstate' => $hppstate,
                'wplogger' => $wplogger,
            ]
        );
    }
    
    public function testExecute()
    {
        $this->assertInstanceOf(Iframe::class, $this->iframeObj);
    }
}
