<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Notification;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Exception;

class Sample extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;

    protected $_rawBody;
    /**
     * @var \Sapient\Worldpay\Model\HistoryNotificationFactory
     */
    protected $historyNotification;

    const RESPONSE_OK = '[OK]';
    const RESPONSE_FAILED = '[FAILED]';
    
    private $cron;

    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Payment\Service $paymentservice
     * @param \Sapient\Worldpay\Model\Token\WorldpayToken $worldpaytoken
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     * @param \Sapient\Worldpay\Model\HistoryNotificationFactory $historyNotification
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice,
        \Sapient\Worldpay\Model\Token\WorldpayToken $worldpaytoken,
        \Sapient\Worldpay\Model\Order\Service $orderservice,
        \Sapient\Worldpay\Model\HistoryNotificationFactory $historyNotification,
        \Sapient\Worldpay\Cron\RecurringOrders $cron
    ) {
        parent::__construct($context);
        $this->wplogger = $wplogger;
        $this->paymentservice = $paymentservice;
        $this->orderservice = $orderservice;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->historyNotification = $historyNotification;
        $this->worldpaytoken = $worldpaytoken;
        $this->cron = $cron;
    }

    public function execute()
    {
        //echo 'sanju';
        $order = $this->cron->execute();
        
        return true;
    }
}
