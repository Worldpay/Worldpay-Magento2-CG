<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Checkout\Hpp\Json;

use Sapient\Worldpay\Model\Checkout\Hpp\Json\Url\Config as UrlConfig;
use Exception;

class Config
{
    const TYPE_IFRAME = 'iframe';

    /** @var string $type Indicates whether you are using an iframe or lightbox integration. */
    private $type;

    /** @var string $iframeIntegrationID Specifies the name of the reference to the integration library.*/
    private $iframeIntegrationID;

    /** @var string $iframeHelperURL The URL of the helper library that you are hosting on your website. */
    private $iframeHelperURL;

    /**
     * @var string $iframeBaseURL The URL of the webpage on your website that is hosting the
     * integrated payment pages.
     */
    private $iframeBaseURL;

    /**
     * @var string $url The redirect URL that we send you in response to a valid XML order.
     * The URL is for the Hosted Payment Pages.
     */
    private $url;

    /** @var string $target The ID for the element into which the iframe will be injected. */
    private $target;

    /** @var boolean $debug When set to true, debug messages are written to the console. */
    private $debug;

    /** @var string $language The default language setting for the payment pages. */
    private $language;

    /** @var string $country The default country setting for the payment pages. */
    private $country;

    private $preferredPaymentMethod;

    private $urlConfig;
    
    public function __construct(
        $type,
        $iframeIntegrationID,
        $iframeHelperURL,
        $iframeBaseURL,
        $url,
        $target,
        UrlConfig $urlConfig,
        $language = 'en',
        $country = 'gb',
        $preferredPaymentMethod = null,
        $debug = false
    ) {
       
        $availableTypes = [self::TYPE_IFRAME];

        if (!in_array($type, $availableTypes)) {
            throw new \InvalidArgumentException(
                sprintf('Possible values for type parameter are %s.', implode(', ', $availableTypes))
            );
        }
        if (empty($iframeIntegrationID)) {
            throw new \InvalidArgumentException('iframeIntegrationID parameter must be set.');
        }
        if (empty($iframeHelperURL)) {
            throw new \InvalidArgumentException('iframeHelperURL parameter must be set.');
        }
        if (empty($iframeBaseURL)) {
            throw new \InvalidArgumentException('iframeBaseURL parameter must be set.');
        }
        if (empty($url)) {
            throw new \InvalidArgumentException('url parameter must be set.');
        }
        if (empty($target)) {
            throw new \InvalidArgumentException('target parameter must be set.');
        }
        if ($debug !== null && !is_bool($debug)) {
            throw new \InvalidArgumentException('debug parameter must be a boolean.');
        }

        $this->type = $type;
        $this->iframeIntegrationID = $iframeIntegrationID;
        $this->iframeHelperURL = $iframeHelperURL;
        $this->iframeBaseURL = $iframeBaseURL;
        $this->url = $url;
        $this->target = $target;
        $this->debug = $debug;
        $this->language = $language;
        $this->country = $country;
        $this->preferredPaymentMethod = $preferredPaymentMethod;
        $this->urlConfig = $urlConfig;
    }

    /**
     * Return type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Retrieve integration id
     *
     * @return string
     */
    public function getIframeIntegrationID()
    {
        return $this->iframeIntegrationID;
    }

    /**
     * Return iframe helper url
     *
     * @return string
     */
    public function getIframeHelperURL()
    {
        return $this->iframeHelperURL;
    }

    /**
     * Return iframe base url
     *
     * @return string
     */
    public function getIframeBaseURL()
    {
        return $this->iframeBaseURL;
    }

    /**
     * Return url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Return Target value
     *
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Return config value
     *
     * @return boolean
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * Return language configured
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Return Country value
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }
    
    /**
     * Return config url
     *
     * @return string
     */
    public function getUrlConfig()
    {
        return $this->urlConfig;
    }

    /**
     * Return preferred payment method
     *
     * @return string
     */
    public function getPreferredPaymentMethod()
    {
        return $this->preferredPaymentMethod;
    }
}
