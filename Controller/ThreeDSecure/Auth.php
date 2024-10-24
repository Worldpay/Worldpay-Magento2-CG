<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Controller\ThreeDSecure;

use Magento\Framework\App\Action\Context;
use Exception;
use \Magento\Framework\Controller\ResultFactory;

class Auth extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $wplogger;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var _assetRepo
     */
    protected $_assetRepo;

     /**
      * @var \Sapient\Worldpay\Helper\Data
      */
    protected $worldpayHelper;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var \Magento\Framework\App\Request\Http
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
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param Sapient\Worldpay\Helper\Data $worldpayHelper
     * @param ResultFactory $resultFactory
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     *
     */
    public function __construct(
        Context $context,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Sapient\Worldpay\Helper\Data $worldpayHelper,
        ResultFactory $resultFactory,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
    ) {
        $this->wplogger = $wplogger;
        $this->checkoutSession = $checkoutSession;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_assetRepo = $assetRepo;
        $this->worldpayHelper = $worldpayHelper;
        $this->resultFactory = $resultFactory;
        $this->request = $request;
        $this->_cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        parent::__construct($context);
    }

    /**
     * Renders the 3D Secure  page, responsible for forwarding All necessary order data to worldpay
     */
    public function execute()
    {
        $threeDSecureChallengeParams = $this->checkoutSession->get3Ds2Params();

        $threeDSecureChallengeConfig = $this->checkoutSession->get3DS2Config();
        $directOrderParams = $this->checkoutSession->getDirectOrderParams();
        $orderId = $this->checkoutSession->getAuthOrderId();
        $iframe = false;
        // Chrome 84 releted updates for 3DS
        $skipSameSiteForIOs = $this->worldpayHelper->shouldSkipSameSiteNone($directOrderParams);
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
                /*setcookie("PHPSESSID", $phpsessId, [
                'expires' => time() + 3600,
                'path' => '/',
                'domain' => $domain,
                'secure' => true,
                'httponly' => true,
                ]);*/
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
                /*setcookie("PHPSESSID", $phpsessId, [
                'expires' => time() + 3600,
                'path' => '/',
                'domain' => $domain,
                'secure' => true,
                'httponly' => true,
                'samesite' => 'None',
                ]);*/
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
        //setcookie("PHPSESSID", $phpsessId, time() + 3600, "/; SameSite=None; Secure;");

        if (!$threeDSecureChallengeConfig == null) {

            if ($threeDSecureChallengeConfig['challengeWindowType'] == 'iframe') {
                $iframe = true;
            }
        }
        if ($redirectData = $this->checkoutSession->get3DSecureParams()) {
            // Chrome 84 releted updates for 3DS
//            $phpsessId = $_COOKIE['PHPSESSID'];
//          setcookie("PHPSESSID", $phpsessId, time() + 3600, "/; SameSite=None; Secure;");
            $responseUrl = $this->_url->getUrl('worldpay/threedsecure/authresponse', ['_secure' => true]);

            $resContent = '
                <form name="theForm" id="form" method="POST" action=' . $redirectData->getUrl() . '>
                    <input type="hidden" name="PaReq" value=' . $redirectData->getPaRequest() . ' />
                    <input type="hidden" name="TermUrl" value=' . $responseUrl . ' />
                </form>';

            $resContent .='
                <script language="Javascript">
                    document.getElementById("form").submit();
                </script>';

            $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $result->setHeader('Content-Type', 'text/html');
            $result->setContents($resContent);
            return $result;
        } elseif ($threeDSecureChallengeParams) {
            if ($iframe) {
                $challengeUrl = $this->_url->getUrl("worldpay/hostedpaymentpage/challenge");
                $imageurl = $this->_assetRepo->getUrl("Sapient_Worldpay::images/cc/worldpay_logo.png");

                $resContent = '
                    <div id="challenge_window">
                        <div class="image-content" style="text-align: center;">
                            <img src=' . $imageurl . ' alt="WorldPay"/>
                        </div>
                        <div class="iframe-content">
                            <iframe src="' . $challengeUrl . '" name="jwt_frm" id="jwt_frm"
                                style="text-align: center; vertical-align: middle; height: 400px;
                                display: table-cell; margin: 0 25%;
                                width: -moz-available; width: -webkit-fill-available;
                                width: fill-available; z-index:999999; z-index:999999;">
                            </iframe>
                        </div>
                    </div>
                    </script>';
                $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
                $result->setHeader('Content-Type', 'text/html');
                $result->setContents($resContent);
                return $result;
            } else {
                $authUrl = $this->_url->getUrl('worldpay/threedsecure/ChallengeAuthResponse', ['_secure' => true]);

                $payload = [
                    'ACSUrl' => $threeDSecureChallengeParams['acsURL'],
                    'Payload' => $threeDSecureChallengeParams['payload'],
                    'TransactionId' => $threeDSecureChallengeParams['transactionId3DS'],
                ];
                $encodedJTW = $this->worldpayHelper->createSecondJWTtoken($authUrl, $payload);
                $resContent ='
                    <form name= "challengeForm" id="challengeForm"
                    method= "POST"
                    action="' . $threeDSecureChallengeConfig["challengeurl"] . '" >
                    <!-- Use the above Challenge URL for test,
                    we will provide a static Challenge URL for production once you go live -->
                        <input type = "hidden" name= "JWT" id= "second_jwt" value= "" />
                        <!-- Encoding of the JWT above with the secret "worldpaysecret". -->
                        <input type="hidden" name="MD" value=' . $orderId . ' />
                        <input type="hidden" name="url" value=' . $authUrl . ' />
                        <!--
                        Extra field for you to pass data in to the challenge that will be included in the post
                        back to the return URL after challenge complete
                        -->
                    </form>';

                    $resContent .='
                    <script language="Javascript">
                        document.getElementById("second_jwt").value = "' . $encodedJTW . '";
                        window.onload = function()
                        {
                          // Auto submit form on page load
                          document.getElementById("challengeForm").submit();
                        }
                    </script>';

                $this->checkoutSession->uns3DS2Params();
                $this->checkoutSession->uns3DS2Config();

                $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
                $result->setHeader('Content-Type', 'text/html');
                $result->setContents($resContent);
                return $result;
            }
        } elseif ($this->checkoutSession->getIavCall()) {
            $this->checkoutSession->unsIavCall();
            return $this->resultRedirectFactory->create()->setPath('worldpay/savedcard', ['_current' => true]);
        } else {
            return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success', ['_current' => true]);
        }
    }
}
