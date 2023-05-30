<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Logger;

use DateTimeZone;
use Monolog\Handler\HandlerInterface;

class WorldpayLogger extends \Monolog\Logger
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * Constructor
     *
     * @param string $name
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param array $handlers
     * @param array $processors
     * @param DateTimeZone $timezone
     *
     */
    public function __construct(
        string $name,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array $handlers = [],
        array $processors = [],
        ?DateTimeZone $timezone = null
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct(
            $name,
            $handlers,
            $processors,
            $timezone ?: new DateTimeZone(date_default_timezone_get() ?: 'UTC')
        );
    }
    /**
     * Adds a log record.
     *
     * @param integer $level The logging level
     * @param string $message The log message
     * @param array $context The log context
     * @param string $datetime for log date and time
     * @return bool Whether the record has been processed
     */
    public function addRecord($level, $message, array $context = [], $datetime = null) : bool
    {
        $logEnabled = (bool) $this->scopeConfig->getValue('worldpay/general_config/enable_logging');

        if ($logEnabled) {
            return parent::addRecord($level, $message, $context);
        }
        return false;
    }
}
