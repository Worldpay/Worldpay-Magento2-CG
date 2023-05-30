<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Request;

use Exception;

class AuthenticationService extends \Magento\Framework\DataObject
{

    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $_wplogger;

     /**
      * @var \Sapient\Worldpay\Helper\Data
      */
    protected $worldpayhelper;

    /**
     * Constructor
     *
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Helper\Data $worldpayhelper
     */
    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Helper\Data $worldpayhelper
    ) {
        $this->_wplogger = $wplogger;
        $this->worldpayhelper = $worldpayhelper;
    }

    /**
     * Authentication request
     *
     * @param string $params
     * @param string $type
     *
     * @return bool
     */
    public function requestAuthenticated($params, $type = 'ecom')
    {
        return true;
    }
}
