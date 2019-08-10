<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\ThreeDSecure;

use Magento\Framework\App\Action\Context;
use Exception;

class Auth extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(Context $context,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->wplogger = $wplogger;
        $this->checkoutSession = $checkoutSession;
        $this->_resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Renders the 3D Secure  page, responsible for forwarding
     * all necessary order data to worldpay.
     */
    public function execute()
    {        
        $threeDSecureChallengeParams = $this->checkoutSession->get3DS2Params();
        $threeDSecureChallengeConfig = $this->checkoutSession->get3DS2Config();
        $orderId = $this->checkoutSession->getAuthOrderId();
        if ($redirectData = $this->checkoutSession->get3DSecureParams()) {
            print_r('
                <form name="theForm" id="form" method="POST" action='.$redirectData->getUrl().'>
                    <input type="hidden" name="PaReq" value='.$redirectData->getPaRequest().' />
                    <input type="hidden" name="TermUrl" value='.$this->_url->getUrl('worldpay/threedsecure/authresponse', ['_secure' => true]).' />
                </form>');
            print_r('
                <script language="Javascript">
                    document.getElementById("form").submit();
                </script>');
        } else if($threeDSecureChallengeParams){
            print_r(' 
<!-- An example 3DS2 challenge window size. -->
    <form name= "challengeForm" id="challengeForm" method= "POST" action="'.$threeDSecureChallengeConfig["challengeurl"].'" >
    <!-- Use the above Challenge URL for test, we will provide a static Challenge URL for production once you go live -->
        <input type = "hidden" name= "JWT" id= "second_jwt" value= "" />
        <!-- Encoding of the JWT above with the secret "worldpaysecret". -->
        <input type="hidden" name="MD" value='.$orderId.' />
        <input type="hidden" name="url" value='.$this->_url->getUrl("worldpay/threedsecure/challengeauthresponse", ["_secure" => true]).' 
        <!-- Extra field for you to pass data in to the challenge that will be included in the post back to the return URL after challenge complete -->
    </form>');
            print_r('
                <script src="//cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.2/rollups/hmac-sha256.js"></script>
                <script src="//cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.2/components/enc-base64-min.js"></script>
                <script language="Javascript">
                var header = {
                "typ": "JWT",
                "alg": "HS256"
            };
            
            var iat = Math.floor(new Date().getTime()/1000);
            var jti = uuidv4();
            var data = {
              "jti": jti,
              "iat": iat,
              "iss": "'.$threeDSecureChallengeConfig["jwtIssuer"].'",
              "OrgUnitId": "'.$threeDSecureChallengeConfig["organisationalUnitId"].'",
			  "ReturnUrl": "'.$this->_url->getUrl('worldpay/threedsecure/ChallengeAuthResponse', ['_secure' => true]).'",
			  "Payload": {
					"ACSUrl": "'.$threeDSecureChallengeParams['acsURL'].'",
					"Payload": "'.$threeDSecureChallengeParams['payload'].'",
					"TransactionId": "'.$threeDSecureChallengeParams['transactionId3DS'].'"
				},
				"ObjectifyPayload": true
            };
            var secret = "fa2daee2-1fbb-45ff-4444-52805d5cd9e0";

            var stringifiedHeader = CryptoJS.enc.Utf8.parse(JSON.stringify(header));
            var encodedHeader = base64url(stringifiedHeader);

            var stringifiedData = CryptoJS.enc.Utf8.parse(JSON.stringify(data));
            var encodedData = base64url(stringifiedData);

            var signature = encodedHeader + "." + encodedData;
            signature = CryptoJS.HmacSHA256(signature, secret);
            signature = base64url(signature);
            var encodedJWT = encodedHeader + "." + encodedData + "." + signature;
            document.getElementById("second_jwt").value = encodedJWT;
            function uuidv4() {
                return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
                  (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
                );
            }
        
            function base64url(source) {
                // Encode in classical base64
                var encodedSource = CryptoJS.enc.Base64.stringify(source);

                // Remove padding equal characters
                encodedSource = encodedSource.replace(/=+$/, "");

                // Replace characters according to base64url specifications
                encodedSource = encodedSource.replace(/\+/g, "-");
                encodedSource = encodedSource.replace(/\//g, "_");

                return encodedSource;
            }
            window.onload = function()
            {
              // Auto submit form on page load
              document.getElementById("challengeForm").submit();
            }
                    
            </script>');
        } else {
            return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success', ['_current' => true]);
        }
    }
}
