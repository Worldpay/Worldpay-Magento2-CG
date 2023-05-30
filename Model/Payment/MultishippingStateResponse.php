<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment;

class MultishippingStateResponse
{
    /**
     * @var \Sapient\Worldpay\Model\Payment\StateResponseFactory
     */
    protected $stateResponse;
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
     * Extract merchant code from params
     *
     * @param Array $params
     */
    public function extractMerchantCodeFromParams($params)
    {
        $orderkey =  $params['orderKey'];
        $extractMerchantCode = explode('^', $orderkey);
        $merchantCode = $extractMerchantCode[1];
        return $merchantCode;
    }

    /**
     * Create Response
     *
     * @param string $params
     * @param string $ordercode
     * @param string $status
     */
    public function createResponse($params, $ordercode, $status)
    {
        $merchantCode = $this->extractMerchantCodeFromParams($params);
        $stateResponseObj = null;
        switch ($status) {
            case 'cancelled':
                $stateResponseObj = new \Sapient\Worldpay\Model\Payment\StateResponse(
                    $ordercode,
                    $merchantCode,
                    \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_CANCELLED,
                    null
                );
                break;
            case 'pending':
                $stateResponseObj = new \Sapient\Worldpay\Model\Payment\StateResponse(
                    $ordercode,
                    $merchantCode,
                    \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_PENDING_PAYMENT,
                    null
                );
                break;
            default:
                $stateResponseObj = new \Sapient\Worldpay\Model\Payment\StateResponse(
                    $ordercode,
                    $merchantCode,
                    \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_PENDING_PAYMENT,
                    null
                );
                break;
        }
        return $stateResponseObj;
    }
    /**
     * Create Cancelled Response
     *
     * @param string $ordercode
     * @param string $merchantCode
     */
    public function createCancelledResponse($ordercode, $merchantCode)
    {
        $stateResponseObj = null;
        $stateResponseObj = new \Sapient\Worldpay\Model\Payment\StateResponse(
            $ordercode,
            $merchantCode,
            \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_CANCELLED,
            null
        );
        return $stateResponseObj;
    }
}
