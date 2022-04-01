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
use Laminas\Uri\UriFactory;
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
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Sales\Model\Order $mageOrder,
        $services = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->assetRepo = $assetrepo;
        $this->request = $request;
        $this->wplogger = $wplogger;
        $this->worldpayhelper = $worldpayhelper;
        $this->quoteRepository = $quoteRepository;
        $this->mageorder = $mageOrder;
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
        $parts = UriFactory::factory($this->state->getRedirectUrl());

        $orderparams = $parts->getQueryAsArray();
        $orderkey = $orderparams['OrderKey'];
        $magentoincrementid = $this->_extractOrderId($orderkey);
        $mageOrder = $this->mageorder->loadByIncrementId($magentoincrementid);
        $quote = $this->quoteRepository->get($mageOrder->getQuoteId());
        
        $country = $this->_getCountryForQuote($quote);
        $language = $this->_getLanguageForLocale();

        $params = ['_secure' => $this->request->isSecure()];
        $helperhtml = $this->assetRepo->getUrlWithParams('Sapient_Worldpay::helper.html', $params);
        $iframeurl = 'worldpay/redirectresult/iframe';
        $urlConfig = new UrlConfig(
            $this->store->getUrl($iframeurl, ['status' => 'success']),
            $this->store->getUrl($iframeurl, ['status' => 'cancel']),
            $this->store->getUrl($iframeurl, ['status' => 'pending']),
            $this->store->getUrl($iframeurl, ['status' => 'error']),
            $this->store->getUrl($iframeurl, ['status' => 'failure'])
        );
      
        return new Config(
            $this->worldpayhelper->getRedirectIntegrationMode($this->store->getId()),
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

    private function _getCountryForQuote($quote)
    {
        $address = $quote->getBillingAddress();
        if ($address->getId()) {
            return $address->getCountry();
        }

        return $this->worldpayhelper->getDefaultCountry();
    }

    private function _getLanguageForLocale()
    {
        $locale = $this->worldpayhelper->getLocaleDefault();
        if (substr($locale, 3, 2) == 'NO') {
            return 'no';
        }
        return substr($locale, 0, 2);
    }
    
    private function _extractOrderId($orderKey)
    {
        $array = explode('^', $orderKey);
        $ordercode = end($array);
        $ordercodearray = explode('-', $ordercode);
        return reset($ordercodearray);
    }
}
