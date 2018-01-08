<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model;
/**
 * Resource Model
 */
class HistoryNotification extends \Magento\Framework\Model\AbstractModel
{
	/**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Sapient\Worldpay\Model\ResourceModel\HistoryNotification');
    }
}
