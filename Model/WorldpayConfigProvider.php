<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Sapient\Worldpay\Model\PaymentMethods\CreditCards as WorldPayCCPayment;
use Magento\Checkout\Model\Cart;
use Sapient\Worldpay\Model\SavedTokenFactory;

/**
 * Configuration provider for worldpayment rendering payment page.
 */
class WorldpayConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string[]
     */
    protected $methodCodes = [
        'worldpay_cc',
        'worldpay_apm'
    ];

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    protected $methods = [];
    /**
     * @var \Sapient\Worldpay\Model\PaymentMethods\Creditcards
     */
    protected $payment ;
    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    protected $worldpayHelper;
    /**
     * @var Magento\Checkout\Model\Cart
     */
    protected $cart;
    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $wplogger;

    /**
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Helper\Data $helper
     * @param PaymentHelper $paymentHelper
     * @param WorldPayCCPayment $payment
     * @param Cart $cart
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Backend\Model\Session\Quote $adminquotesession
     * @param SavedTokenFactory $savedTokenFactory
     * @param \Magento\Backend\Model\Auth\Session $backendAuthSession
     */
    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Helper\Data $helper,
        PaymentHelper $paymentHelper,
        WorldPayCCPayment $payment,
        Cart $cart,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Backend\Model\Session\Quote $adminquotesession,
        SavedTokenFactory $savedTokenFactory,
        \Magento\Backend\Model\Auth\Session $backendAuthSession
        ) {
            foreach ($this->methodCodes as $code) {
                $this->methods[$code] = $paymentHelper->getMethodInstance($code);
            }
            $this->cart = $cart;
            $this->payment = $payment;
            $this->worldpayHelper = $helper;
            $this->wplogger = $wplogger;
            $this->customerSession = $customerSession;
            $this->backendAuthSession = $backendAuthSession;
            $this->adminquotesession = $adminquotesession;
            $this->savedTokenFactory = $savedTokenFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = [];
        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment']['total'] = $this->cart->getQuote()->getGrandTotal();
                $config['payment']['minimum_amount'] = $this->payment->getMinimumAmount();
                if ($code=='worldpay_cc') {
                    $config['payment']['ccform']["availableTypes"][$code] = $this->getCcTypes();
                } else {
                    $config['payment']['ccform']["availableTypes"][$code] = $this->getApmTypes($code);
                }
                $config['payment']['ccform']["hasVerification"][$code] = true;
                $config['payment']['ccform']["hasSsCardType"][$code] = false;
                $config['payment']['ccform']["months"][$code] = $this->getMonths();
                $config['payment']['ccform']["years"][$code] = $this->getYears();
                $config['payment']['ccform']["cvvImageUrl"][$code] = "http://".$_SERVER['SERVER_NAME']."/pub/static/frontend/Magento/luma/es_MX/Magento_Checkout/cvv.png";
                $config['payment']['ccform']["ssStartYears"][$code] = $this->getStartYears();
                $config['payment']['ccform']['intigrationmode'] = $this->getIntigrationMode();
                $config['payment']['ccform']['cctitle'] = $this->getCCtitle();
                $config['payment']['ccform']['isCvcRequired'] = $this->getCvcRequired();
                $config['payment']['ccform']['cseEnabled'] = $this->worldpayHelper->isCseEnabled();

                if ($config['payment']['ccform']['cseEnabled']) {
                    $config['payment']['ccform']['csePublicKey'] = $this->worldpayHelper->getCsePublicKey();
                }

                $config['payment']['ccform']['is3DSecureEnabled'] = $this->worldpayHelper->is3DSecureEnabled();
                $config['payment']['ccform']['savedCardList'] = $this->getSaveCardList();
                $config['payment']['ccform']['saveCardAllowed'] = $this->worldpayHelper->getSaveCard();
                $config['payment']['ccform']['apmtitle'] = $this->getApmtitle();
                $config['payment']['ccform']['paymentMethodSelection'] = $this->getPaymentMethodSelection();
            }
        }
        return $config;
    }
     
    /**
     * Get Saved card List of customer
     */
    public function getSaveCardList()
    {
        $savedCardsList = array();
        if ($this->customerSession->isLoggedIn() || $this->backendAuthSession->isLoggedIn()) {
            $savedCardsList = $this->savedTokenFactory->create()->getCollection()
           ->addFieldToFilter('customer_id', $this->customerSession->getCustomerId())->getData();
        }
        return $savedCardsList;
    }

    /**
     * @return boolean
     */
    public function getIsSaveCardAllowed()
    {
        if ($this->worldpayHelper->getSaveCard()) {
            return true;
        }
        return false;
    }

    /**
     * @return String
     */
    public function getIntigrationMode()
    {
        return $this->worldpayHelper->getCcIntegrationMode();
    }

    /**
     * @return Array
     */
    public function getCcTypes()
    {
        $options = $this->worldpayHelper->getCcTypes();
        if (!empty($this->getSaveCardList()) || !empty($this->getSaveCardListForAdminOrder($this->adminquotesession->getCustomerId()))) {
             $options['savedcard'] = 'Use Saved Card';
        }
        return $options;
     }

    /**
     * @return Array
     */
    public function getApmTypes($code)
    {
        return $this->worldpayHelper->getApmTypes($code);
    }

    public function getMonths()
    {
        return array(
            "01" => "01 - January",
            "02" => "02 - February",
            "03" => "03 - March",
            "04" => "04 - April",
            "05" => "05 - May",
            "06" => "06 - June",
            "07" => "07 - July",
            "08" => "08 - August",
            "09" => "09 - September",
            "10"=> "10 - October",
            "11"=> "11 - November",
            "12"=> "12 - December"
        );
    }

    /**
     * @return Array
     */
    public function getYears()
    {
        $years = array();
        for ($i=0; $i<=10; $i++) {
            $year = (string)($i+date('Y'));
            $years[$year] = $year;
        }
        return $years;
    }

    /**
     * @return Array
     */
    public function getStartYears()
    {
        $years = array();
        for ($i=5; $i>=0; $i--) {
            $year = (string)(date('Y')-$i);
            $years[$year] = $year;
        }
        return $years;
    }

    /**
     * @return String
     */
    public function getCCtitle()
    {
        return $this->worldpayHelper->getCcTitle();
    }

    /**     
     * @return String
     */
    public function getApmtitle()
    {
        return $this->worldpayHelper->getApmTitle();
    }

    /**     
     * @return boolean
     */
    public function getCvcRequired()
    {
        return $this->worldpayHelper->isCcRequireCVC();
    }

    /**     
     * @return string
     */
    public function getPaymentMethodSelection()
    {
        return $this->worldpayHelper->getPaymentMethodSelection();
    }

    /**     
     * @return string
     */
    public function getSaveCardListForAdminOrder($customer)
    {
        $savedCardsList = array();
        if ($this->customerSession->isLoggedIn() || $this->backendAuthSession->isLoggedIn()) {
            $savedCardsList = $this->savedTokenFactory->create()->getCollection()
           ->addFieldToFilter('customer_id', $customer)->getData();
        }
        return $savedCardsList;
    }

}
