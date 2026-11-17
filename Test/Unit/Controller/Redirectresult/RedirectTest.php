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
    use \Sapient\Worldpay\Test\Unit\ObjectFactory;

    /**
     * @var Redirect
     */
    protected $redirectObj;

    protected function setUp(): void
    {
        $context = $this->createStub(Context::class);
        $page = $this->createStub(PageFactory::class);
        $checkoutsession = $this->createStub(Session::class);
        $mageOrder = $this->createStub(Order::class);

        $this->redirectObj = $this->createObject(
            Redirect::class,
            [
                'context' => $context,
                'pageFactory' => $page,
                'checkoutsession' => $checkoutsession,
                'mageOrder' => $mageOrder,
            ]
        );
    }

    public function testExecute()
    {
        $this->assertInstanceOf(Redirect::class, $this->redirectObj);
    }
}
