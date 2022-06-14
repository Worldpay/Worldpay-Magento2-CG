<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Sapient\Worldpay\Block;

use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Sapient\Worldpay\Model\WorldpayConfigProvider;
use Sapient\Worldpay\Helper\Data;

/**
 * Description of SavedCardLink
 *
 * @author aatrai
 */
class SavedCardLink extends \Magento\Framework\View\Element\Html\Link\Current
{

    /**
     * @var $_scopeConfig
     */
    
    protected $_scopeConfig = null;
    /**
     * Constructor
     *
     * @param string $context
     * @param string $config
     * @param string $helper
     * @param string $defaultPath
     * @param array $data
     */

    public function __construct(
        Context $context,
        WorldpayConfigProvider $config,
        Data $helper,
        DefaultPathInterface $defaultPath,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath);
        $this->config = $config;
        $this->helper = $helper;
    }
    /**
     * Display Html
     *
     * @return string
     */

    public function _toHtml()
    {
  
        if ($this->helper->isWorldPayEnable() && $this->checkSaveCardTabToBeEnabled()) {
             return parent::_toHtml();
        } else {
            return '';
        }
    }
    /**
     * Check Save Card TabTo Be Enabled
     *
     * @return string
     */

    public function checkSaveCardTabToBeEnabled()
    {
        if ($this->helper->getSaveCard() ||
                !empty($this->config->getSaveCardListForMyAccount())) {
            return true;
        }
    }
}
