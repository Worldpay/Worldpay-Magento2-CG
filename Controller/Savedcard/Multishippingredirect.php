<?php
/**
 * @copyright 2022 Sapient
 */
namespace Sapient\Worldpay\Controller\Savedcard;

use Magento\Framework\App\Action\Context;
use Exception;

class Multishippingredirect extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */

    protected $checkoutSession;

    /**
     * @var \Sapient\Worldpay\Model\Token\WorldpayToken
     */
    protected $wplogger;

    /**
     * Constructor
     *
     * @param Context $context
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        Context $context,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->wplogger = $wplogger;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context);
    }
    /**
     * Perform redirect
     */

    public function execute()
    {
        $threeDSecureChallengeParams = $this->checkoutSession->get3Ds2Params();
                
        if ($redirectData = $this->checkoutSession->get3DSecureParams()) {
            return $this->resultRedirectFactory->create()->setPath('worldpay/threedsecure/auth', ['_current' => true]);
        } elseif ($threeDSecureChallengeParams) {
            return $this->resultRedirectFactory->create()->setPath('worldpay/threedsecure/auth', ['_current' => true]);
        } else {
            $url = 'multishipping/checkout/success';
            return $this->resultRedirectFactory->create()->setPath($url, ['_current' => true]);
        }
    }
}
