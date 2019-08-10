<?php
namespace Sapient\Worldpay\Block;

use Sapient\Worldpay\Helper\Data;

class Jwt extends \Magento\Framework\View\Element\Template
{
    /**
    * @var Sapient\Worldpay\Helper\Data;
    */
    
    protected $helper;
    
    /**
     * Jwt constructor.
     * @param Create $helper
     * @param array $data
     */

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        Data $helper)
    {
        $this->_helper = $helper;
        parent::__construct($context);
    }

    public function getJwtIssuer()
    {
        return $this->_helper->isJwtIssuer();
    }
    
    public function getOrganisationalUnitId()
    {
        return $this->_helper->isOrganisationalUnitId();
    }
    
    public function getDdcUrl()
    {
        $ddcurl = '';
        $mode = $this->_helper->getEnvironmentMode();
        if($mode == 'Test Mode'){
            $ddcurl =  $this->_helper->isTestDdcUrl();
        } else {
            $ddcurl =  $this->_helper->isProductionDdcUrl();
        }
        return $ddcurl;
    }
    
    
}