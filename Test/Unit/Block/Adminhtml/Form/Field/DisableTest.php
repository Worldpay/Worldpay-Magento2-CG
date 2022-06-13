<?php
/** * @copyright 2017 Sapient */
namespace Sapient\Worldpay\Test\Unit\Block\Adminhtml\Form\Field;

use \PHPUnit\Framework\TestCase;
use Sapient\Worldpay\Block\Adminhtml\Form\Field\Disable;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Escaper;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class DisableTest extends TestCase
{
    
    /**
     * @var \Sapient\Worldpay\Block\Adminhtml\Form\Field\Disable
     */
    protected $disbaleObj;
    /**
     * @var Text
     */
    protected $element;
    protected function setUp(): void
    {
            $context = $this->getMockBuilder(Context::class)
                ->disableOriginalConstructor()
                ->getMock();
            $factoryElement = $this->getMockBuilder(Factory::class)
                ->disableOriginalConstructor()
                ->getMock();
            $factoryCollection = $this->getMockBuilder(CollectionFactory::class)
                ->disableOriginalConstructor()
                ->getMock();
            $secureHtmlRenderer = $this->getMockBuilder(SecureHtmlRenderer::class)
                ->disableOriginalConstructor()
                ->getMock();
            $escaper = $this->getMockBuilder(Escaper::class)
                ->disableOriginalConstructor()
                ->getMock();
            $this->disbaleObj = new Disable($context, [], $secureHtmlRenderer, $escaper);
            $this->element = $this->getMockBuilder(AbstractElement::class)
                ->disableOriginalConstructor()
                ->getMock();
    }
    public function testgetElementHtml()
    {
        $this->assertNull($this
            ->element
            ->setData('readonly', 1));
        $this->assertNull($this
            ->element
            ->getElementHtml());
    }
}
