<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Token;

/**
 * Interface Sapient_WorldPay_Model_Token_UpdateInterface
 *
 * Describe what can be read from WP's token update response
 */
interface UpdateInterface
{
    /**
     * Retrive tokencode
     *
     * @return string
     */
    public function getTokenCode();

    /**
     * Is success?
     *
     * @return boolean
     */
    public function isSuccess();
}
