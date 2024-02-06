<?php

namespace Sapient\Worldpay\Helper;

use \Magento\Framework\App\Helper\Context;
use \Sapient\Worldpay\Model\Mail\Template\EmailTransportBuilder as TransportBuilder;
use \Magento\Framework\Translate\Inline\StateInterface;
use \Magento\Store\Model\StoreManagerInterface;

class SendRecurringOrdersEmail extends \Magento\Framework\App\Helper\AbstractHelper
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
     * SendRecurringOrdersEmail constructor
     *
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param StateInterface $state
     */
    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        StateInterface $state
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->wplogger = $wplogger;
        $this->storeManager = $storeManager;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $state;
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
        try {
            // template variables pass here
            $templateVars = $params;
            $templateId = $params['mail_template'];
            $subject = __("WORLDPAY: ").
            "Reminder - Recurring order";

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
            $this->wplogger->info(__('Unable to send email '). $e->getMessage());
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
    public function sendRecurringOrdersEmail($params)
    {
        try {
                $customerEmail = $params['email'];
            if (!empty($customerEmail)) {
                foreach ($customerEmail as $recipientEmail) {
                    $this->wplogger->info("Recurring Order email to ".$recipientEmail);
                    $this->sendEmail($params, $recipientEmail);
                }
            }
            
        } catch (\Exception $e) {
            $this->wplogger->info(__("Failed to send email:").$e->getMessage());
        }
    }
}
