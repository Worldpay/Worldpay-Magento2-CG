<?php
namespace Sapient\Worldpay\Block\Adminhtml\System\Config;
 
use Magento\Framework\Registry;
use Magento\Backend\Block\Template\Context;
use Magento\Cms\Model\Wysiwyg\Config as WysiwygConfig;
 
class Editor extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var WysiwygConfig
     */
    protected $_wysiwygConfig;
    /**
     * @var  Registry
     */
    protected $_coreRegistry;
 
    /**
     * @param Context       $context
     * @param WysiwygConfig $wysiwygConfig
     * @param array         $data
     */
    public function __construct(
        Context $context,
        WysiwygConfig $wysiwygConfig,
        array $data = []
    ) {
        $this->_wysiwygConfig = $wysiwygConfig;
        parent::__construct($context, $data);
    }
    /**
     * Magento\Framework\Data\Form\Element\AbstractElement
     *
     * @param Element $element
     */
 
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->setWysiwyg(true);
        $confgiData = $this->_wysiwygConfig->getConfig($element);
        $confgiData->setplugins([]);
        $confgiData->setadd_variables(0);
        $confgiData->setadd_widgets(0);
        
        $element->setConfig($confgiData);
        return parent::_getElementHtml($element);
    }
}
