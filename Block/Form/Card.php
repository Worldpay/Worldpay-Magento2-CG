<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Block\Form;

use Magento\Customer\Controller\RegistryConstants;

class Card extends \Magento\Payment\Block\Form
{
   
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
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->configProvider = $configProvider;
        $this->_coreRegistry = $registry;
        $this->adminquotesession = $session;
        $this->worldpayhelper = $worldpayhelper;
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

    public function requireCvcEnabled()
    {
        return $this->worldpayhelper->isCcRequireCVC();
    }

    public function getSavedCards()
    {
        return $this->configProvider->getSaveCardListForAdminOrder($this->getCustomerId());
    }
    public function getCustomerId()
    {
        return $this->adminquotesession->getCustomerId();
    }
    
    public function getCCtypes()
    {
        return $this->configProvider->getCcTypes();
    }

    public function getIntegrationMode()
    {
        return $this->worldpayhelper->getCcIntegrationMode();
    }

    public function isDirectIntegration()
    {
        $integrationmode = $this->getIntegrationMode();
        return $integrationmode === 'direct';
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
}
