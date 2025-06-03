<?php
namespace Sapient\Worldpay\Block;

use Sapient\Worldpay\Helper\Data;

class Challenge extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Sapient\Worldpay\Helper\Data;
     */

    protected $_helper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Jwt constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param Data $helper
     * @param array $data
     */

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        Data $helper,
        array $data = []
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->_helper = $helper;
        parent::__construct($context, $data);
    }
    /**
     * Get JWT user
     *
     * @return string
     */

    public function getJwtIssuer()
    {
        return $this->_helper->isJwtIssuer();
    }
    /**
     * Get Unit Id
     *
     * @return string
     */

    public function getOrganisationalUnitId()
    {
        return $this->_helper->isOrganisationalUnitId();
    }
    /**
     * Get DdcUrl
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
     * Get Challenge Configs
     *
     * @return array
     */

    public function challengeConfigs()
    {
        $threeDSecureChallengeParams = $this->checkoutSession->get3Ds2Params();

        return [
            'challengeurl' => $this->checkoutSession->get3DS2Config()['challengeurl'],
            'orderId' => $this->checkoutSession->getAuthOrderId(),
            'encodedJWT' => $this->_helper->createSecondJWTtoken(
                $this->getUrl('worldpay/threedsecure/challengeredirectresponse', ['_secure' => true]),
                [
                    'ACSUrl' => $threeDSecureChallengeParams['acsURL'],
                    'Payload' => $threeDSecureChallengeParams['payload'],
                    'TransactionId' => $threeDSecureChallengeParams['transactionId3DS'],
                ]
            ),
        ];
    }
}
