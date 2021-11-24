<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Block\Form;

use Magento\Customer\Controller\RegistryConstants;

class Card extends \Magento\Payment\Block\Form
{
    const MOTO_CONFIG = 'moto_config';
   
    private $worldpayPaymentsMoto;
    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context,
     * @param \Sapient\Worldpay\Model\WorldpayConfigProvider $configProvider,
     * @param \Magento\Payment\Helper\Data $paymentHelper,
     * @param \Sapient\Worldpay\Helper\Data $worldpayhelper,
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\Model\Session\Quote $session,
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Sapient\Worldpay\Model\WorldpayConfigProvider $configProvider,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Sapient\Worldpay\Helper\Data $worldpayhelper,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Model\Session\Quote $session,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Payment\PaymentTypes $paymenttypes,
        \Sapient\Worldpay\Model\Payment\LatAmInstalTypes $latamtypes,
        \Magento\Backend\Model\Session\Quote $adminsessionquote,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->configProvider = $configProvider;
        $this->_coreRegistry = $registry;
        $this->adminquotesession = $session;
        $this->worldpayhelper = $worldpayhelper;
        $this->latamtypes = $latamtypes;
        $this->wplogger = $wplogger;
        $this->paymenttypes = $paymenttypes;
        $this->adminsessionquote = $adminsessionquote;
        $this->worldpayPaymentsMoto = $paymentHelper->getMethodInstance('worldpay_moto');
    }
    
    public function getClientKey()
    {
        return true;
    }
    
    public function saveCardEnabled()
    {
        return $this->configProvider->getIsSaveCardAllowed();
    }
    
    public function tokenizationEnabled()
    {
        return $this->worldpayhelper->getTokenization();
    }
    
    public function storedCredentialsEnabled()
    {
        return $this->worldpayhelper->getStoredCredentials();
    }

    public function requireCvcEnabled()
    {
        return $this->worldpayhelper->isCcRequireCVC();
    }

    public function getSavedCards()
    {
        $savedcardlists = $this->configProvider->getSaveCardListForAdminOrder($this->getCustomerId());
        $lookuppaymenttypes = $this->getLookUpPaymentTypes();
        $filterccards =  [];
        foreach ($savedcardlists as $savedcardlist) {
            if (in_array($savedcardlist['method'], $lookuppaymenttypes)) {
                $filterccards[] = $savedcardlist;
            }
        }
        return $filterccards;
    }
    public function getCustomerId()
    {
        return $this->adminquotesession->getCustomerId();
    }
    
    public function getCCtypes()
    {
        $cctypes = $this->configProvider->getCcTypes(self::MOTO_CONFIG);
        $lookuppaymenttypes = $this->getLookUpPaymentTypes();
        $filtercctypes =  [];
        if (!empty($lookuppaymenttypes)) {
            foreach ($cctypes as $k => $cctype) {
                if ($k != 'savedcard') {
                    if (in_array($k, $lookuppaymenttypes)) {
                        $filtercctypes[$k] = $cctype;
                    }
                } else {
                    $filtercctypes[$k] = $cctype;
                }
            }
        } else {
            $filtercctypes = $lookuppaymenttypes;
        }
        return $filtercctypes;
    }

    public function getIntegrationMode()
    {
        return $this->worldpayhelper->getMotoIntegrationMode();
    }

    public function isDirectIntegration()
    {
        $integrationmode = $this->getIntegrationMode();
        return $integrationmode === 'moto_direct';
    }
    
    public function isRedirectIntegration()
    {
        $integrationmode = $this->getIntegrationMode();
        return $integrationmode === 'moto_redirect';
    }
    
    public function cpfEnabled()
    {
        $adminQuote = $this->adminsessionquote->getQuote();
        $address = $adminQuote->getBillingAddress();
        $countryId = $address->getCountryId();
        if ($countryId == 'BR') {
            return $this->worldpayhelper->isCPFEnabled();
        }
        return false;
    }

    public function instalmentEnabled()
    {
        return $this->worldpayhelper->isInstalmentEnabled();
    }
    
    public function getInstalmentTypes()
    {
        $adminQuote = $this->adminsessionquote->getQuote();
        $address = $adminQuote->getBillingAddress();
        $countryId = $address->getCountryId();
        $filtertypes = [];
        $countries = ['AR', 'BZ', 'BR', 'CL', 'CO', 'CR', 'SV', 'GT', 'HN', 'MX', 'NI', 'PA', 'PE'];
        if (in_array($countryId, $countries)) {
            $latamtypes = $this->latamtypes->getInstalmentType($countryId);
            if (!empty($latamtypes)) {
                $filtertypes = explode(", ", $latamtypes);
            }
        }
        return $filtertypes;
    }

    public function getMonths()
    {
        $currentMonth = (int)date('m');
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $monthnumber = ($x <= 12) ? $x : $x-12;
            $months[$monthnumber] = date('F', mktime(0, 0, 0, $x, 1));
        }
        return $months;
    }

    public function getYears()
    {
        return $this->configProvider->getYears();
    }

    public function getLookUpPaymentTypes()
    {
        $adminQuote = $this->adminsessionquote->getQuote();
        $address = $adminQuote->getBillingAddress();
        $countryId = $address->getCountryId();
        $paymenttypes = $this->paymenttypes->getPaymentType($countryId);
        return json_decode($paymenttypes);
    }
    
    public function getJsonData($wpData)
    {
        $serializedData = '';
        if ($wpData !== null) {
            $serializedData = json_encode($wpData);
        }
        return $serializedData;
    }
    
    public function getCheckoutSpecificLabel($labelcode)
    {
        $data = $this->configProvider->getCheckoutLabels();
        if (is_array($data) || is_object($data)) {
            foreach ($data as $key => $valuepair) {
                if ($valuepair['wpay_label_code'] == $labelcode) {
                    return $valuepair['wpay_custom_label']?
                            $valuepair['wpay_custom_label']:$valuepair['wpay_label_desc'];
                }
            }
        }
    }
}
