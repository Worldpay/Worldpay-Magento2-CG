<?php
namespace Sapient\Worldpay\Controller\Hostedpaymentpage;

class Challenge extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    ) {
        $this->_pageFactory = $pageFactory;
        return parent::__construct($context);
    }

    public function execute()
    {
        if (isset($_COOKIE['PHPSESSID'])) {
            $phpsessId = $_COOKIE['PHPSESSID'];
            if (phpversion() < '7.3.0') {
                setcookie("PHPSESSID", $phpsessId, time() + 3600, "/; SameSite=None; Secure;");
            }else {
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
