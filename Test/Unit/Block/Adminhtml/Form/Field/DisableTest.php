<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Test\Unit\Block\Adminhtml\Form\Field;

use \PHPUnit\Framework\TestCase;
use Sapient\Worldpay\Block\Adminhtml\Form\Field\Disable;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Escaper;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;

class DisableTest extends TestCase
{
    /**
     * [$disbaleObj description]
     * @var [type]
     */
    protected $disbaleObj;
    /**
     * [$element description]
     * @var [type]
     */
    protected $element;
    /**
     * [setUp description]
     */
    protected function setUp()
    {
        $context = $this->getMockBuilder(Context::class)
                        ->disableOriginalConstructor()->getMock();
        $factoryElement = $this->getMockBuilder(Factory::class)
                        ->disableOriginalConstructor()->getMock();
        $factoryCollection = $this->getMockBuilder(CollectionFactory::class)
                        ->disableOriginalConstructor()->getMock();
        $escaper = $this->getMockBuilder(Escaper::class)
                        ->disableOriginalConstructor()->getMock();
        $this->disbaleObj = new Disable($context, [], $factoryCollection, $escaper);
        $this->element = $this->getMockBuilder(AbstractElement::class)
                ->disableOriginalConstructor()
                ->getMock();
    }
    /**
     * [testgetElementHtml description]
     * @return [type] [description]
     */
    public function testgetElementHtml()
    {
        $this->assertNull($this->element->setData('readonly', 1));
        $this->assertNull($this->element->getElementHtml());
    }
}
