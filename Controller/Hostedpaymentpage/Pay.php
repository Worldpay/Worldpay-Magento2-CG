<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Hostedpaymentpage;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;

/**
 * Redirect to payment hosted page
 */
class Pay extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;

    /**
     * @var \Sapient\Worldpay\Model\Checkout\Hpp\State
     */
    protected $_status;

    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $wplogger;

    /**
     * @var \Sapient\Worldpay\Model\Checkout\Hpp\State
     */
    protected $hppstate;
    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    protected $worldpayhelper;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param \Sapient\Worldpay\Model\Checkout\Hpp\State $hppstate
     * @param \Sapient\Worldpay\Helper\Data $worldpayhelper
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     */

    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        \Sapient\Worldpay\Model\Checkout\Hpp\State $hppstate,
        \Sapient\Worldpay\Helper\Data $worldpayhelper,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
    ) {
        $this->pageFactory = $pageFactory;
        $this->wplogger = $wplogger;
        $this->hppstate = $hppstate;
        $this->worldpayhelper = $worldpayhelper;
        return parent::__construct($context);
    }
    /**
     * Execute
     *
     * @return string
     */
    public function execute()
    {

        if (!$this->_getStatus()->isInitialised() || !$this->worldpayhelper->isIframeIntegration()) {
            return $this->resultRedirectFactory->create()->setPath('checkout/cart', ['_current' => true]);
        }
        return $this->pageFactory->create();
    }
    /**
     * Get Status
     *
     * @return string
     */

    protected function _getStatus()
    {
        if ($this->_status === null) {
            $this->_status = $this->hppstate;
        }

        return $this->_status;
    }
}
