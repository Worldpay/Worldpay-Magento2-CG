<?php
/**
 * Copyright © 2020 Worldpay. All rights reserved.
 */

namespace Sapient\Worldpay\Model\Recurring;

use Magento\Framework\Exception\LocalizedException;

/**
 * Recurring Plan
 *
 * @method int getProductId()
 * @method \Sapient\Worldpay\Model\Recurring\Plan setProductId(int $value)
 * @method int getWebsiteId()
 * @method \Sapient\Worldpay\Model\Recurring\Plan setWebsiteId(int $value)
 * @method string getCode()
 * @method \Sapient\Worldpay\Model\Recurring\Plan setCode(string $value)
 * @method string getName()
 * @method \Sapient\Worldpay\Model\Recurring\Plan setName(string $value)
 * @method string getDescription()
 * @method \Sapient\Worldpay\Model\Recurring\Plan setDescription(string $value)
 * @method int getNumberOfPayments()
 * @method \Sapient\Worldpay\Model\Recurring\Plan setNumberOfPayments(int $value)
 * @method string getInterval()
 * @method \Sapient\Worldpay\Model\Recurring\Plan setInterval(string $value)
 * @method float getIntervalAmount()
 * @method \Sapient\Worldpay\Model\Recurring\Plan setIntervalAmount(float $value)
 * @method string getTrialInterval()
 * @method \Sapient\Worldpay\Model\Recurring\Plan setTrialInterval(string $value)
 * @method int getNumberOfTrialIntervals()
 * @method \Sapient\Worldpay\Model\Recurring\Plan setNumberOfTrialIntervals(int $value)
 * @method int getSortOrder()
 * @method \Sapient\Worldpay\Model\Recurring\Plan setSortOrder(int $value)
 * @method int getActive()
 * @method \Sapient\Worldpay\Model\Recurring\Plan setActive(int $value)
 * @method string getLitleTxnId()
 * @method \Sapient\Worldpay\Model\Recurring\Plan setLitleTxnId(string $value)
 *
 */
class Plan extends \Magento\Framework\Model\AbstractModel
{

    /**
     * Plan constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Sapient\Worldpay\Model\ResourceModel\Recurring\Plan::class);
    }
    
    /**
     * Load plan Details
     *
     * @param int $planId
     * @return array
     */
    public function loadById($planId)
    {
        if (!$planId) {
            return;
        }
        $id = $this->getResource()->loadById($planId);
            return $this->load($id);
    }
}
