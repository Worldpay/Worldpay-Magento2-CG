<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Savedcard;

use Magento\Framework\App\Action\Context;
use Exception;

class Instantredirect extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var resultRedirect
     */
    protected $redirect;
   
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
     * @param \Magento\Framework\App\Response\RedirectInterface $redirect
     */

    public function __construct(
        Context $context,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Response\RedirectInterface $redirect
    ) {
        $this->wplogger = $wplogger;
        $this->checkoutSession = $checkoutSession;
        $this->redirect = $redirect;
        parent::__construct($context);
    }

    /**
     * Perform Instant redirect
     */
    public function execute()
    {
        $threeDSecureChallengeParams = $this->checkoutSession->get3Ds2Params();
        $instantRedirectUrl = $this->redirect->getRefererUrl();
//      $this->messageManager->getMessages(true);
        if ($redirectData = $this->checkoutSession->get3DSecureParams()) {
            $this->checkoutSession->setInstantPurchaseOrder(true);
            $this->checkoutSession->setInstantPurchaseRedirectUrl($instantRedirectUrl);
            return $this->resultRedirectFactory->create()->setPath('worldpay/threedsecure/auth', ['_current' => true]);
        } elseif ($threeDSecureChallengeParams) {
            $this->checkoutSession->setInstantPurchaseRedirectUrl($instantRedirectUrl);
            return $this->resultRedirectFactory->create()->setPath('worldpay/threedsecure/auth', ['_current' => true]);
        } else {
            return $this->resultRedirectFactory->create()->setUrl($instantRedirectUrl);
        }
    }
}
