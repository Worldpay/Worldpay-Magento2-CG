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
     * @var \Magento\Framework\Url\DecoderInterface
     */
    public $decoder;
    
    /**
     * Jwt constructor
     *
     * @param Context $context
     * @param string $helper
     * @param \Magento\Framework\Url\DecoderInterface $decoder
     */

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        Data $helper,
        \Magento\Framework\Url\DecoderInterface $decoder
    ) {
        $this->_helper = $helper;
        $this->decoder = $decoder;
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
        return $this->_helper->getDdcUrl();
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
    /**
     * Create JWT Token
     */
    public function getJwtToken()
    {
        return $this->_helper->createJwtToken();
    }
    /**
     * Get magento decoder class
     */
    public function getDecoder()
    {
        return $this->decoder;
    }
}
