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
     * Log file name
     * @var string
     */
    protected $fileName = '/var/log/worldpay.log';
}
