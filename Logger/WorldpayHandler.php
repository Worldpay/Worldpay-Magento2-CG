<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Logger;

use Monolog\Logger;

class WorldpayHandler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/worldpay.log';
}
