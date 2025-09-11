<?php

namespace Sapient\Worldpay\Helper;

use \Magento\Framework\App\Helper\Context;
use \Sapient\Worldpay\Model\Mail\Template\EmailTransportBuilder as TransportBuilder;
use \Magento\Framework\Translate\Inline\StateInterface;
use \Magento\Store\Model\StoreManagerInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

class SendPayByLinkEmail extends \Magento\Framework\App\Helper\AbstractHelper
{
    public const WORLDPAY_SALES_EMAIL_PAYBYLINK_TEMPLATE = "wp_sales_email_paybylink";
    public const WORLDPAY_SALES_EMAIL_PAYBYLINK_MULTISHIPPING_TEMPLATE = "wp_sales_email_paybylink_multishipping";

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
     *  Check if Pay by link is enabled
     */
    public function isEnablePayByLink()
    {
        return $this->_scopeConfig->getValue(
            'worldpay/paybylink_config/enable',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Get Customer Emails
     */
    public function getCustomerEmail()
    {
        if ($this->_customerSession->isLoggedIn()) {
            $customerEmail = $this->_customerSession->getCustomer()->getEmail();
        } else {
            $customerEmail = $this->checkoutSession->getQuote()->getCustomerEmail();
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
     * Get Customer Emails
     *
     * @param string $customerEmail
     */
    public function getCustomerEmailForResend($customerEmail)
    {
        if (!empty($customerEmail)) {
            $allEmails = explode(',', $customerEmail);
            if (!empty($allEmails)) {
                return $allEmails;
            }
        }
        return [];
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
        $templateId = self::WORLDPAY_SALES_EMAIL_PAYBYLINK_TEMPLATE;
        if ($params['is_multishipping']) {
            $templateId = self::WORLDPAY_SALES_EMAIL_PAYBYLINK_MULTISHIPPING_TEMPLATE;
        }

        try {
            // template variables pass here
            $templateVars = $params;
            $subject = __("WORLDPAY: ").
                __("Pay by Link for Order").' '.$this->checkoutSession->getAuthenticatedOrderId();

            if ($params['is_multishipping']) {
                $subject = __("WORLDPAY: ").
                    __("Pay by Link for Multishipping Order").' '.$params['orderId'];
            }

            if ($params['is_resend']) {
                $subject = __("WORLDPAY: ").
                    __("Resend Pay by Link for Order").' '.$params['orderId'];
            }
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
            $this->wplogger->info(__('Unable to send email'). $e->getMessage());
        }
    }

    /**
     * Get store admin email and name
     */
    public function getAdminContactEmail()
    {
        return [
            'name' =>   $this->_scopeConfig->getValue(
                'trans_email/ident_support/name',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ),
            'email' =>   $this->_scopeConfig->getValue(
                'trans_email/ident_support/email',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ),
        ];
    }
    /**
     * Send Pay By link Email
     *
     * @param array $params
     */
    public function sendPayBylinkEmail($params)
    {
        try {
            if ($this->isEnablePayByLink()) {
                if ($params['is_resend']) {
                    $customerEmail = $this->getCustomerEmailForResend($params['customerEmail']);
                } else {
                    $customerEmail = $this->getCustomerEmail();
                }
                if (!empty($customerEmail)) {
                    foreach ($customerEmail as $recipientEmail) {
                        $this->sendEmail($params, $recipientEmail);
                        $this->wplogger->info("Pay by Link email to ".$recipientEmail);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->wplogger->info(__("Failed to send email:").$e->getMessage());
        }
    }
}
