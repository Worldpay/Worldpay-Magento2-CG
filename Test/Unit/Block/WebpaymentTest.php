<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Test\Unit\Block;

use Magento\Catalog\Block\Product\View as ProductView;
use Magento\Checkout\Block\Cart\AbstractCart;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\DataObject;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\Element\Template\Context;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Sapient\Worldpay\Block\Webpayment;
use Sapient\Worldpay\Helper\Data;
use Sapient\Worldpay\Helper\ProductOnDemand;
use Sapient\Worldpay\Helper\Recurring;

class WebpaymentTest extends TestCase
{
    /**
     * @var Webpayment
     */
    private $block;

    /**
     * @var AbstractCart
     */
    private $cartMock;

    protected function setUp(): void
    {
        $this->cartMock = $this->createMock(AbstractCart::class);

        $this->block = new Webpayment(
            $this->createStub(Context::class),
            $this->cartMock,
            $this->createStub(Data::class),
            $this->createStub(Session::class),
            $this->createStub(TokenFactory::class),
            $this->createStub(ScopeConfigInterface::class),
            $this->createStub(ManagerInterface::class),
            $this->createStub(StoreManagerInterface::class),
            $this->createStub(Recurring::class),
            $this->createStub(SessionManagerInterface::class),
            $this->createStub(SerializerInterface::class),
            $this->createStub(Repository::class),
            $this->createStub(Http::class),
            $this->createStub(ProductView::class),
            $this->createStub(ProductOnDemand::class)
        );
    }

    public function testGetShippingRateReturnsZeroWhenShippingTotalAbsent()
    {
        $this->cartMock->expects($this->once())
            ->method('getTotalsCache')
            ->willReturn([]);

        $this->assertSame(0, $this->block->getShippingRate());
    }

    public function testGetShippingRateReturnsShippingValueWhenPresent()
    {
        $shipping = new DataObject(['value' => 7.5]);

        $this->cartMock->expects($this->once())
            ->method('getTotalsCache')
            ->willReturn(['shipping' => $shipping]);

        $this->assertSame(7.5, $this->block->getShippingRate());
    }
}
