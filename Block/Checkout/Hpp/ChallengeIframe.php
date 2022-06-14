<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Block\Checkout\Hpp;
 
class ChallengeIframe extends \Magento\Framework\View\Element\Template
{
    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Sapient\Worldpay\Model\Checkout\Hpp\Json\Config\Factory $configfactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Sapient\Worldpay\Model\Checkout\Hpp\Json\Config\Factory $configfactory,
        array $data = []
    ) {
        $this->configfactory = $configfactory;
           parent::__construct($context, $data);
    }
    
    /**
     * Get Redirect Url
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->getUrl('worldpay/threedsecure/challengeauthresponse', ['_secure' => true]);
    }
}
