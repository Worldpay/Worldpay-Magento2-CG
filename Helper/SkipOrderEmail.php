<?php

namespace Sapient\Worldpay\Helper;

use \Magento\Framework\App\Helper\Context;
use \Sapient\Worldpay\Model\Mail\Template\EmailTransportBuilder as TransportBuilder;
use \Magento\Framework\Translate\Inline\StateInterface;
use \Magento\Store\Model\StoreManagerInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

class SkipOrderEmail extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $wplogger;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

     /**
      * @var \Magento\Framework\Translate\Inline\StateInterface
      */
    protected $inlineTranslation;
    /**
     * @var \Sapient\Worldpay\Model\Mail\Template\EmailTransportBuilder
     */
    protected $transportBuilder;
    /**
     * @var \Magento\Customer\Model\Session
     */
      protected $_customerSession;
    
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * SendPayByLinkEmail constructor
     *
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param CheckoutSession $checkoutSession
     * @param StateInterface $state
     */
    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        CheckoutSession $checkoutSession,
        StateInterface $state
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->wplogger = $wplogger;
        $this->_customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->transportBuilder = $transportBuilder;
        $this->checkoutSession = $checkoutSession;
        $this->inlineTranslation = $state;
    }

    /**
     * Get Customer Emails
     */
    public function getCustomerEmail()
    {
        if ($this->_customerSession->isLoggedIn()) {
            $customerEmail = $this->_customerSession->getCustomer()->getEmail();
        }
        if (!empty($customerEmail)) {
            $allEmails = explode(',', $customerEmail);
            if (!empty($allEmails)) {
                return $allEmails;
            }
        }
        return [];
    }
    /**
     * Get store admin email and name
     */
    public function getAdminContactEmail()
    {
        return [
            'name' => $this->_scopeConfig->getValue(
                'trans_email/ident_support/name',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ),
            'email' => $this->_scopeConfig->getValue(
                'trans_email/ident_support/email',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ),
        ];
    }
    /**
     * Get Skip Order Notification Email Template
     *
     * @return string
     */
    public function getSkipOrderEmailTemplate()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/subscriptions/skip_order_notification_email',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Send Email
     *
     * @param array $params
     * @param string $toEmail
     */
    public function sendEmail($params, $toEmail)
    {
        $senderDetails = $this->getAdminContactEmail();
        $templateId = $this->getSkipOrderEmailTemplate();

        try {
            // template variables pass here
            $templateVars = $params;
            $subject = __("WORLDPAY: ").
                "Skipped Order".' '.$params['orderId'];

            $templateVars['mail_subject'] = $subject;
            $storeId = $this->storeManager->getStore()->getId();
            $from = ['email' => $senderDetails['email'], 'name' => $senderDetails['name']];
            $this->inlineTranslation->suspend();
  
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $templateOptions = [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $storeId
            ];
            $transport = $this->transportBuilder->setTemplateIdentifier($templateId, $storeScope)
                ->setTemplateOptions($templateOptions)
                ->setTemplateVars($templateVars)
                ->setFrom($from)
                ->addTo($toEmail)
                ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->wplogger->info(__('Unable to send email: '). $e->getMessage());
        }
    }
/**
 * Send Pay By link Email
 *
 * @param array $params
 */
    public function sendSkipOrderEmail($params)
    {
        try {
            $customerEmail = $this->getCustomerEmail();
            if (!empty($customerEmail)) {
                foreach ($customerEmail as $recipientEmail) {
                    $this->sendEmail($params, $recipientEmail);
                    $this->wplogger->info("Skip Order email send to ".$recipientEmail);
                }
            }
        } catch (\Exception $e) {
            $this->wplogger->info(__("Failed to send email:").$e->getMessage());
        }
    }
}
