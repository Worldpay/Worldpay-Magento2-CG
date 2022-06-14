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
     * @param Context $context
     * @param string $helper
     */

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        Data $helper
    ) {
        $this->_helper = $helper;
        parent::__construct($context);
    }
    /**
     * Get Jwt Api Key
     *
     * @return string
     */
    public function getJwtApiKey()
    {
        return $this->_helper->isJwtApiKey();
    }
    /**
     * Get Jwt Is user
     *
     * @return string
     */
    public function getJwtIssuer()
    {
        return $this->_helper->isJwtIssuer();
    }
    /**
     * Get Organisational UnitId
     *
     * @return string
     */
    public function getOrganisationalUnitId()
    {
        return $this->_helper->isOrganisationalUnitId();
    }
   /**
    * Get DDC Url
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
     * Return Cookie
     *
     * @return string
     */

    public function getCookie()
    {
        return $cookie = $this->_helper->getWorldpayAuthCookie();
    }
    /**
     * Get Current Date
     *
     * @return string
     */

    public function getCurrentDate()
    {
        $curdate = date("Y-m-d H:i:s");
        return strtotime(date("Y-m-d H:i:s", strtotime($curdate)). " -1 min");
    }
}
