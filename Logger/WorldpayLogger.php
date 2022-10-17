<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Logger;

class WorldpayLogger extends \Monolog\Logger
{
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
        $ObjectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $logEnabled = (bool) $ObjectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
                            ->getValue('worldpay/general_config/enable_logging');
        if ($logEnabled) {
            return parent::addRecord($level, $message, $context);
        }
        return false;
    }
}
