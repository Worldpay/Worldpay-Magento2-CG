<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment;

class WpResponse
{

    /**
     * @var \Sapient\Worldpay\Model\Payment\StateResponseFactory
     */
    public $stateResponse;
    /**
     * Constructor
     *
     * @param object $stateResponse
     */
    public function __construct(\Sapient\Worldpay\Model\Payment\StateResponseFactory $stateResponse)
    {
        $this->stateResponse = $stateResponse;
    }
    /**
     * Create From Cancelled Response
     *
     * @param string $params
     * @return string
     */
    public function createFromCancelledResponse($params)
    {

        $orderkey =  $params['orderKey'];
        // extract order code
        $extractOrderCode = explode('^', $orderkey);
        $orderCode = end($extractOrderCode);
        // extract merchantcode
        $extractMerchantCode = explode('^', $orderkey);
        $merchantCode = $extractMerchantCode[1];
        return new \Sapient\Worldpay\Model\Payment\StateResponse(
            $orderCode,
            $merchantCode,
            \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_CANCELLED,
            null
        );
    }
    /**
     * Create From Pay By Link Cancelled Response
     *
     * @param string $orderCode
     * @param string $merchantCode
     * @return string
     */
    public function createFromPblCancelledResponse($orderCode, $merchantCode)
    {
        return new \Sapient\Worldpay\Model\Payment\StateResponse(
            $orderCode,
            $merchantCode,
            \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_CANCELLED,
            null
        );
    }

  /**
   * Create from Pending Response
   *
   * @param string $params
   * @param int|bool|null $paymentType
   * @return string
   */

    public function createFromPendingResponse($params, $paymentType = null)
    {
        $orderkey = $params['orderKey'];
         // extract order code
         $extractOrderCode = explode('^', $orderkey);
         $orderCode = end($extractOrderCode);
         // extract merchantcode
         $extractMerchantCode = explode('^', $orderkey);
         $merchantCode = $extractMerchantCode[1];

        if (!empty($paymentType) && $paymentType == "KLARNA-SSL") {
            return new \Sapient\Worldpay\Model\Payment\StateResponse(
                $orderCode,
                $merchantCode,
                \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_SENT_FOR_AUTHORISATION,
                null
            );
        } else {
            return new \Sapient\Worldpay\Model\Payment\StateResponse(
                $orderCode,
                $merchantCode,
                \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_PENDING_PAYMENT,
                null
            );
        }
    }
}
