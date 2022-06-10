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
     * Jwt constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param Data $helper
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        Data $helper
    ) {
        $this->_helper = $helper;
        parent::__construct($context);
    }

    /**
     * Get jwt api key
     *
     * @return string
     */
    public function getJwtApiKey()
    {
        return $this->_helper->isJwtApiKey();
    }
    
    /**
     * Get jwt is user
     *
     * @return string
     */
    public function getJwtIssuer()
    {
        return $this->_helper->isJwtIssuer();
    }
    
    /**
     * Get organisational unit id
     *
     * @return string
     */
    public function getOrganisationalUnitId()
    {
        return $this->_helper->isOrganisationalUnitId();
    }
    
    /**
     * Get ddc url
     *
     * @return string
     */
    public function getDdcUrl()
    {
        $ddcurl = '';
        $mode = $this->_helper->getEnvironmentMode();
        if ($mode == 'Test Mode') {
            $ddcurl =  $this->_helper->isTestDdcUrl();
        } else {
            $ddcurl =  $this->_helper->isProductionDdcUrl();
        }
        return $ddcurl;
    }
    
    /**
     * Retrieve cookie value
     *
     * @return string|null
     */
    public function getCookie()
    {
        return $cookie = $this->_helper->getWorldpayAuthCookie();
    }
    
    /**
     * Get current date
     *
     * @return string
     */
    public function getCurrentDate()
    {
        $curdate = date("Y-m-d H:i:s");
        return strtotime(date("Y-m-d H:i:s", strtotime($curdate)). " -1 min");
    }
}
