<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\ThreeDSecure;

use Sapient\Worldpay\Helper\CreditCardException;

class AuthResponse extends \Magento\Framework\App\Action\Action
{

    /**
     * @var helper
     */
    protected $helper;
    /**
     * @var request
     */
    protected $request;
    /**
     * @var _cookieManager
     */
    protected $_cookieManager;
    /**
     * @var cookieMetadataFactory
     */
    protected $cookieMetadataFactory;
    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Sapient\Worldpay\Model\Authorisation\ThreeDSecureService $threedsredirectresponse
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
     * @param CreditCardException $helper
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Sapient\Worldpay\Model\Authorisation\ThreeDSecureService $threedsredirectresponse,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        CreditCardException $helper,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
    ) {
        $this->wplogger = $wplogger;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->urlBuilder = $context->getUrl();
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->orderSender = $orderSender;
        $this->threedsredirectresponse = $threedsredirectresponse;
        $this->urlBuilders    = $urlBuilder;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->helper = $helper;
        $this->request = $request;
        $this->_cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        parent::__construct($context);
    }
    
    /**
     * Accepts callback from worldpay's 3D Secure page.
     * If payment has been authorised,
     * Update order and redirect to the checkout success page.
     */
    public function execute()
    {
        $directOrderParams = $this->checkoutSession->getDirectOrderParams();
        $threeDSecureParams = $this->checkoutSession->get3DSecureParams();
        $skipSameSiteForIOs = $this->shouldSkipSameSiteNone($directOrderParams);
        $mhost = $this->request->getHttpHost();
        $cookieValue = $this->_cookieManager->getCookie('PHPSESSID');

        if ($skipSameSiteForIOs) {
            $this->wplogger->info("Inside skip same site block");
            if (isset($cookieValue)) {
                
                $phpsessId = $cookieValue;
                $domain = $mhost;
                $expires = time() + 3600;
                $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
                $metadata->setPath('/');
                $metadata->setDomain($domain);
                $metadata->setDuration($expires);
                $metadata->setSecure(true);
                $metadata->setHttpOnly(true);

                $this->_cookieManager->setPublicCookie(
                    "PHPSESSID",
                    $phpsessId,
                    $metadata
                );
            }
        } else {
            $this->wplogger->info("Outside skip same site block");
            if (isset($cookieValue)) {
                $phpsessId = $cookieValue;
                $domain = $mhost;
                $expires = time() + 3600;
                $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
                $metadata->setPath('/');
                $metadata->setDomain($domain);
                $metadata->setDuration($expires);
                $metadata->setSecure(true);
                $metadata->setHttpOnly(true);
                $metadata->setSameSite("None");
                $this->_cookieManager->setPublicCookie(
                    "PHPSESSID",
                    $phpsessId,
                    $metadata
                );
            }
        }
        $this->checkoutSession->unsDirectOrderParams();
        $this->checkoutSession->uns3DSecureParams();
        try {
            $this->threedsredirectresponse->continuePost3dSecureAuthorizationProcess(
                $this->getRequest()->getParam('PaRes'),
                $directOrderParams,
                $threeDSecureParams
            );
        } catch (\Exception $e) {
            $this->wplogger->error($e->getMessage());
            $this->wplogger->error('3DS Failed');
            $this->_messageManager->addError(__($this->helper->getConfigValue('CCAM9')));
             $this->messageManager->addError(__($this->helper->getConfigValue('CCAM9')));
            if ($this->checkoutSession->getInstantPurchaseOrder()) {
                $redirectUrl = $this->checkoutSession->getInstantPurchaseRedirectUrl();
                $this->checkoutSession->unsInstantPurchaseRedirectUrl();
                $this->checkoutSession->unsInstantPurchaseOrder();
                return $this->resultRedirectFactory->create()->setUrl($redirectUrl);
            } elseif ($this->checkoutSession->getIavCall()) {
                $this->checkoutSession->unsIavCall();
                $this->getResponse()->setRedirect($this->urlBuilders->getUrl(
                    'savedcard/addnewcard',
                    ['_secure' => true]
                ));
            } else {
                $this->getResponse()->setRedirect($this->urlBuilders->getUrl('checkout/cart', ['_secure' => true]));
            }
        }
        if ($this->checkoutSession->getInstantPurchaseOrder()) {
            $redirectUrl = $this->checkoutSession->getInstantPurchaseRedirectUrl();
            $this->checkoutSession->unsInstantPurchaseRedirectUrl();
            $this->checkoutSession->unsInstantPurchaseOrder();
            $message=$this->checkoutSession->getInstantPurchaseMessage();
            if ($message) {
                $this->checkoutSession->unsInstantPurchaseMessage();
                $this->messageManager->addSuccessMessage($message);
            }
            return $this->resultRedirectFactory->create()->setUrl($redirectUrl);
        } elseif ($this->checkoutSession->getIavCall()) {
                $this->checkoutSession->unsIavCall();
                $this->getResponse()->setRedirect(
                    $this->urlBuilders->getUrl('worldpay/savedcard', ['_secure' => true])
                );
        } else {
            $redirectUrl = $this->checkoutSession->getWpResponseForwardUrl();
            $this->checkoutSession->unsWpResponseForwardUrl();
            $this->getResponse()->setRedirect($redirectUrl);
        }
    }

    /**
     * ShouldSkip SameSiteNone
     *
     * @param string $directOrderParams
     * @return false;
     */
    public function shouldSkipSameSiteNone($directOrderParams)
    {
        if (isset($directOrderParams)) {
            $useragent = $directOrderParams['userAgentHeader'] ;
            $iosDeviceRegex = "/\(iP.+; CPU .*OS (\d+)[_\d]*.*\) AppleWebKit\//";
            $macDeviceRegex = "/\(Macintosh;.*Mac OS X (\d+)_(\d+)[_\d]*.*\) AppleWebKit\//";
            $iosVersionRegex = '/OS 12./';
            $macVersionRegex ='/OS X 10./';
            $macLatestVersionRegex = '/OS X 10_15_7/';
            if (preg_match($iosDeviceRegex, $useragent) && preg_match($iosVersionRegex, $useragent)) {
                $this->wplogger->info('Passed regex check for ios');
                return true;
            } elseif ((preg_match($macDeviceRegex, $useragent) && preg_match($macVersionRegex, $useragent))
                  && (!preg_match($macLatestVersionRegex, $useragent))) {
                $this->wplogger->info('Passed regex check for mac');
                return true;
            }
            $this->wplogger->info(json_encode($useragent));
            $this->wplogger->info('Outside regex check');
            return false;
        }
        return false;
    }
}
