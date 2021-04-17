<?php
/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Test\Unit\Controller\Redirectresult;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;
use \PHPUnit\Framework\TestCase;
use Sapient\Worldpay\Controller\Redirectresult\Redirect;

class RedirectTest extends TestCase
{

    protected $redirectObj;

    protected function setUp(): void
    {
        $context = $this->getMockBuilder(Context::class)
                        ->disableOriginalConstructor()->getMock();
        $page = $this->getMockBuilder(PageFactory::class)
                        ->disableOriginalConstructor()->getMock();
        $checkoutsession = $this->getMockBuilder(Session::class)
                        ->disableOriginalConstructor()->getMock();
        $mageOrder = $this->getMockBuilder(Order::class)
                        ->disableOriginalConstructor()->getMock();

        $this->redirectObj = new Redirect($context, $page, $checkoutsession, $mageOrder);
    }

    public function testExecute()
    {
        $this->assertInstanceOf(Redirect::class, $this->redirectObj);
    }
}
