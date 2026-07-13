<?php
namespace Sapient\Worldpay\Controller\Hostedpaymentpage;

class Challenge extends \Magento\Framework\App\Action\Action
{
    /**
     * @var $_pageFactory
     */
    protected $_pageFactory;
    /**
     * @var $request
     */
    protected $request;
    /**
     * @var $_cookieManager
     */
    protected $_cookieManager;
    /**
     * @var $cookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    protected $worldpayHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * Constructor
     *
     * @param Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Sapient\Worldpay\Helper\Data $worldpayHelper
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     */

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Sapient\Worldpay\Helper\Data $worldpayHelper,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
    ) {
        $this->_pageFactory = $pageFactory;
        $this->checkoutSession = $checkoutSession;
        $this->worldpayHelper = $worldpayHelper;
        $this->request = $request;
        $this->_cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        return parent::__construct($context);
    }
    /**
     * Execute
     *
     * @return string
     */
    public function execute()
    {
        $directOrderParams = $this->checkoutSession->getDirectOrderParams();
        $skipSameSiteForIOs = $this->worldpayHelper->shouldSkipSameSiteNone($directOrderParams);

        $mhost = $this->request->getHttpHost();
        $cookieValue = $this->_cookieManager->getCookie('PHPSESSID');

        if (isset($cookieValue)) {
            $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
            $metadata->setPath('/');
            $metadata->setDomain($mhost);
            $metadata->setDuration(3600);
            $metadata->setSecure(true);
            $metadata->setHttpOnly(true);

            if (!$skipSameSiteForIOs) {
                $metadata->setSameSite("None");
            }

            $this->_cookieManager->setPublicCookie(
                "PHPSESSID",
                $cookieValue,
                $metadata
            );
        }

        return $this->_pageFactory->create();
    }
}
