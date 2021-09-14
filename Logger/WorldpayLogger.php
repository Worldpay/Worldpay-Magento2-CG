<?php
/**
 * Copyright © 2021 Worldpay, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sapient\Worldpay\Logger;

class WorldpayLogger extends \Monolog\Logger
{
    
    public function addRecord($level, $message, array $context = [])
    {
        $ObjectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $logEnabled = (bool) $ObjectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
                                ->getValue('worldpay/general_config/enable_logging');
        if ($logEnabled) {
            return parent::addRecord($level, $message, $context);
        }
    }
}
