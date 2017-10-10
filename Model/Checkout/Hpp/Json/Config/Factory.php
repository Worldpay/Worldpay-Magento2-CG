<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Checkout\Hpp\Json\Config;

use Sapient\Worldpay\Model\Checkout\Hpp\Json\Config as Config;
use Sapient\Worldpay\Model\Checkout\Hpp\Json\Url\Config as UrlConfig;
use \Sapient\Worldpay\Model\Checkout\Hpp\State as HppState;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\App\RequestInterface;
use Exception;

class Factory
{
   /**  @var \Magento\Store\Model\Store*/
    private $store; 

    /**
     * @param StoreManagerInterface $storeManager,
     * @param \Sapient\Worldpay\Model\Checkout\Hpp\State $hppstate,
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
     * @param Repository $assetrepo,
     * @param RequestInterface $request,
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
     * @param \Sapient\Worldpay\Helper\Data $worldpayhelper
     */
    public function __construct(
         StoreManagerInterface $storeManager,
        \Sapient\Worldpay\Model\Checkout\Hpp\State $hppstate,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Repository $assetrepo,
        RequestInterface $request,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Helper\Data $worldpayhelper,
        $services = array()
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->assetRepo = $assetrepo;
        $this->request = $request;
        $this->wplogger = $wplogger;
        $this->worldpayhelper = $worldpayhelper;
        if (isset($services['store']) && $services['store'] instanceof StoreManagerInterface) {
            $this->store = $services['store'];
        } else {
            $this->store = $storeManager->getStore();
        }
        if (isset($services['state']) && $services['state'] instanceof \Sapient\Worldpay\Model\Checkout\Hpp\State) {
            $this->state = $services['state'];
        } else {
            $this->state = $hppstate;
        } 
    }

    /**
     * @return Sapient\Worldpay\Model\Checkout\Hpp\Json\Config
     */
    public function create($javascriptObjectVariable, $containerId)
    {
        list($language, $country) = explode('_', $this->scopeConfig->getValue('general/locale/code'));

        $params = array('_secure' => $this->request->isSecure());
        $helperhtml = $this->assetRepo->getUrlWithParams('Sapient_Worldpay::helper.html', $params);
        $urlConfig = new UrlConfig(
            $this->store->getUrl('worldpay/redirectresult/iframe', array('status' => 'success')),
            $this->store->getUrl('worldpay/redirectresult/iframe', array('status' => 'cancel')),
            $this->store->getUrl('worldpay/redirectresult/iframe', array('status' => 'pending')),
            $this->store->getUrl('worldpay/redirectresult/iframe', array('status' => 'error')),
            $this->store->getUrl('worldpay/redirectresult/iframe', array('status' => 'failure'))
        );
      
        return new Config(
            $this->worldpayhelper->getRedirectIntegrationMode( $this->store->getId()),
            $javascriptObjectVariable,
            $helperhtml,
            $this->store->getBaseUrl(),
            $this->state->getRedirectUrl(),
            $containerId,
            $urlConfig,
            strtolower($language),
            strtolower($country)
        );
    }

}
