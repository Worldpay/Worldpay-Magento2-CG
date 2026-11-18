<?php
/** * @copyright 2017 Sapient */
namespace Sapient\Worldpay\Test\Unit\Block\Adminhtml\Form\Field;

use PHPUnit\Framework\TestCase;
use Sapient\Worldpay\Block\Adminhtml\Form\Field\Disable;
use Magento\Framework\Data\Form\Element\AbstractElement;

class DisableTest extends TestCase
{
    public function testGetElementHtml()
    {
        $block = (new \ReflectionClass(Disable::class))->newInstanceWithoutConstructor();
        $element = $this->createMock(AbstractElement::class);
        $element->expects($this->once())->method('setData')->with('readonly', 1)->willReturnSelf();
        $element->expects($this->once())->method('getElementHtml')->willReturn('<input readonly>');

        $method = new \ReflectionMethod(Disable::class, '_getElementHtml');
        $this->assertSame('<input readonly>', $method->invoke($block, $element));
    }
}
