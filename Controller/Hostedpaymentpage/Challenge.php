<?php
namespace Sapient\Worldpay\Controller\Hostedpaymentpage;

class Challenge extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Sapient\Worldpay\Helper\Data $worldpayHelper,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    ) {
        $this->_pageFactory = $pageFactory;
        $this->checkoutSession = $checkoutSession;
        $this->worldpayHelper = $worldpayHelper;
        return parent::__construct($context);
    }

    public function execute()
    {
        $directOrderParams = $this->checkoutSession->getDirectOrderParams();
        $skipSameSiteForIOs = $this->worldpayHelper->shouldSkipSameSiteNone($directOrderParams);
        
        //$this->wplogger->info("SKIP same site value--->".print_r($skipSameSiteForIOs,true));
        
        if ($skipSameSiteForIOs) {
            if (isset($_COOKIE['PHPSESSID'])) {
                $phpsessId = $_COOKIE['PHPSESSID'];
                $domain = parse_url($this->_url->getUrl(), PHP_URL_HOST);
                setcookie("PHPSESSID", $phpsessId, [
        
                'expires' => time() + 3600,
                'path' => '/',
                'domain' => $domain,
                'secure' => true,
                'httponly' => true,
                ]);
            }
        } else {
            if (isset($_COOKIE['PHPSESSID'])) {
                $phpsessId = $_COOKIE['PHPSESSID'];
                $domain = parse_url($this->_url->getUrl(), PHP_URL_HOST);
                setcookie("PHPSESSID", $phpsessId, [
                'expires' => time() + 3600,
                'path' => '/',
                'domain' => $domain,
                'secure' => true,
                'httponly' => true,
                'samesite' => 'None',
                ]);
            }
        }

        return $this->_pageFactory->create();
    }
}
