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
    /**
     * Create JWT Token
     */
    public function getJwtToken()
    {
        $params = $this->getRequest()->getParams();
        $jwtApiKey = $this->getJwtApiKey();
        $jwtIssuer = $this->getJwtIssuer();
        $orgUnitId = $this->getOrganisationalUnitId();
        $iat = $this->getCurrentDate();
        $jwtTokenId    = base64_encode(random_bytes(16));
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'jti' => $jwtTokenId,
            'iat' => $iat,
            'iss' => $jwtIssuer,
            'OrgUnitId' => $orgUnitId,
        ]);
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $jwtApiKey, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
        return $jwt;
    }
    /**
     * Get magento decoder class
     */
    public function getDecoder()
    {
        return $this->decoder;
    }
}
