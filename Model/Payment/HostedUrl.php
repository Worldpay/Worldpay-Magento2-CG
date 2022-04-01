<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment;

use Sapient\Worldpay\Api\HostedUrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Exception;

class HostedUrl implements HostedUrlInterface
{

     /**
      * @var \Sapient\Worldpay\Model\Mapping\Service
      */
    protected $mappingService;
    /**
     * @var \Sapient\Worldpay\Model\Request\PaymentServiceRequest
     */
    protected $paymentservicerequest;
    /**
     * @var \Sapient\Worldpay\Model\Response\RedirectResponse
     */
    protected $redirectresponse;
    protected $assetrepo;
    protected $request;
    protected $checkoutsession;
    protected $wplogger;
    protected $worldpayhelper;
    protected $quoteRepository;
    protected $storeManager;
    protected $quoteIdMaskFactory;
    
    /**
     * @param \Sapient\Worldpay\Model\Mapping\Service               $mappingService
     * @param \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest
     * @param \Sapient\Worldpay\Model\Response\RedirectResponse     $redirectresponse
     * @param Magento\Framework\View\Asset\Repository               $assetrepo
     * @param Magento\Framework\App\RequestInterface                $request
     * @param \Magento\Checkout\Model\Session                       $checkoutsession
     * @param \Sapient\Worldpay\Logger\WorldpayLogger               $wplogger
     * @param \Sapient\Worldpay\Helper\Data                         $worldpayhelper
     * @param \Magento\Quote\Api\CartRepositoryInterface            $quoteRepository
     * @param Magento\Store\Model\StoreManagerInterface             $storeManager
     */
    public function __construct(
        \Sapient\Worldpay\Model\Mapping\Service $mappingService,
        \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest,
        \Sapient\Worldpay\Model\Response\RedirectResponse $redirectresponse,
        Repository $assetrepo,
        RequestInterface $request,
        \Magento\Checkout\Model\Session $checkoutsession,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Helper\Data $worldpayhelper,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        StoreManagerInterface $storeManager,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->paymentservicerequest = $paymentservicerequest;
        $this->mappingService = $mappingService;
        $this->redirectresponse = $redirectresponse;
        $this->request = $request;
        $this->assetRepo = $assetrepo;
        $this->checkoutsession = $checkoutsession;
        $this->wplogger = $wplogger;
        $this->quoteRepository = $quoteRepository;
        $this->worldpayhelper= $worldpayhelper;
        $this->store = $storeManager->getStore();
    }

    /**
     * Retrive HPP payment Url
     *
     * @param  string   $quoteId.
     * @param  string[] $paymentdetails.
     * @return null|string
     */
    public function getHostedUrl($quoteId, array $paymentdetails)
    {
        try {
            if (is_numeric($quoteId)) {
                $quoteId = $quoteId;
            } else { // if we have masked quote Id
                $quoteIdMask = $this->quoteIdMaskFactory->create()->load($quoteId, 'masked_id');
                $quoteId = $quoteIdMask->getQuoteId();
            }
            $quote = $this->quoteRepository->get($quoteId);
            $quote->reserveOrderId();
            $orderStoreId = $quote->getStoreId();
            $Paymentdetails = [];
            $Paymentdetails['method'] = 'worldpay_cc';
            $Paymentdetails['additional_data'] = $paymentdetails;
            $quote->reserveOrderId()->save(); // generating reserve id here and saved the reserved id in the checkout
            $reservedOrderId = $quote->getReservedOrderId();
            $orderCode =  $this->_generateOrderCode($reservedOrderId);
            $this->checkoutsession->setIframePay(true);
            $this->checkoutsession->setHppOrderCode($orderCode);
            $redirectOrderParams = $this->mappingService->collectRedirectOrderParameters(
                $orderCode,
                $quote,
                $orderStoreId,
                $Paymentdetails
            );
          
            $response = $this->paymentservicerequest->redirectOrder($redirectOrderParams);
            $redirectUrl = $this->redirectresponse->getRedirectUrl($response);
            $params = ['_secure' => $this->request->isSecure()];
            $helperhtml = $this->assetRepo->getUrlWithParams('Sapient_Worldpay::helper.html', $params);
            $country = $this->_getCountryForQuote($quote);
            $locale = $this->_getLanguageForLocale();
            $jsConfig = [
                'type' => 'iframe',
                'iframeIntegrationId' =>'checkoutWorldPayLibraryObject' ,
                'iframeHelperURL' => $helperhtml,
                'iframeBaseURL' => $this->store->getBaseUrl(),
                'url' => $redirectUrl,
                'target' => 'checkout-payment-worldpay-container',
                'debug' => 'false',
                "inject"=> 'immediate',
                'language' => strtolower($locale),
                'country' => strtolower($country),
            ];
            return json_encode($jsConfig);
        
        } catch (Exception $e) {
            $this->wplogger->error('Failed while getting Iframe payment url. Error:'.$e->getMessage());
            $errormessage = $this->worldpayhelper->updateErrorMessage($e->getMessage(), $quote->getReservedOrderId());
            throw new \Magento\Framework\Exception\LocalizedException(
                __($errormessage)
            );
        }
    }
    /**
     *  generate order code for HPP Iframe
     *
     * @param  string $reservedOrderId.
     * @return string
     */
    private function _generateOrderCode($reservedOrderId)
    {
        return $reservedOrderId . '-' . time();
    }
    /**
     *  Get country from quote
     *
     * @return string
     */
    private function _getCountryForQuote($quote)
    {
        $address = $quote->getBillingAddress();
        if ($address->getId()) {
            return $address->getCountry();
        }

        return $this->worldpayhelper->getDefaultCountry();
    }
    /**
     * get locale language
     *
     * @return string
     */
    private function _getLanguageForLocale()
    {
        $locale = $this->worldpayhelper->getLocaleDefault();
        if (substr($locale, 3, 2) == 'NO') {
            return 'no';
        }
        return substr($locale, 0, 2);
    }
}
