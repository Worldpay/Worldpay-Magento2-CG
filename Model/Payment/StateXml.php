<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment;

/**
 * Reading xml
 */
class StateXml implements \Sapient\Worldpay\Model\Payment\StateInterface
{
    /** @var xml */
    private $_xml;
    /**
     * Constructor
     * @param xml $xml
     */
    public function __construct($xml)
    {
        $this->_xml = $xml;
    }

    /**
     * Retrive ordercode from xml
     *
     * @return string
     */
    public function getOrderCode()
    {
        $statusNode = $this->_getStatusNode();
        return (string) $statusNode['orderCode'];
    }

    /**
     * Retrive ordercode from xml
     *
     * @return string
     */
    public function getPaymentStatus()
    {
        $statusNode = $this->_getStatusNode();
        return (string) $statusNode->payment->lastEvent;
    }

    /**
     * Retrive status node from xml
     *
     * @return xml
     */
    private function _getStatusNode()
    {
        if (isset($this->_xml->reply)) {
            return $this->_xml->reply->orderStatus;
        }

        return $this->_xml->notify->orderStatusEvent;
    }

    /**
     * Retrive amount from xml
     *
     * @return string
     */
    public function getAmount()
    {
        $statusNode = $this->_getStatusNode();
        return (string) $statusNode->payment->amount['value'];
    }

    /**
     * Retrive merchant code from xml
     *
     * @return string
     */
    public function getMerchantCode()
    {
        return (string) $this->_xml['merchantCode'];
    }

    /**
     * Retrive Risk Score from xml
     *
     * @return string
     */
    public function getRiskScore()
    {
        $statusNode = $this->_getStatusNode();
        return (string) $statusNode->payment->riskScore['value'];
    }

    /**
     * Retrive payment method from xml
     *
     * @return string
     */
    public function getPaymentMethod()
    {
        $statusNode = $this->_getStatusNode();
        return (string) $statusNode->payment->paymentMethod;
    }

    /**
     * Retrive card number from xml
     *
     * @return string
     */
    public function getCardNumber()
    {
        /** @var SimpleXMLElement $statusNode */
        $statusNode = $this->_getStatusNode();
        if (isset($statusNode->payment->cardNumber)) {
            return (string) $statusNode->payment->cardNumber;
        } elseif (isset($statusNode->payment->paymentMethodDetail->card)) {
            return (string) $statusNode->payment->paymentMethodDetail->card['number'];
        }
    }

    /**
     * Retrive avs result code from xml
     *
     * @return string
     */
    public function getAvsResultCode()
    {
        $statusNode = $this->_getStatusNode();
        return (string) $statusNode->payment->AVSResultCode['description'];
    }

    /**
     * Retrive cvc result code from xml
     *
     * @return string
     */
    public function getCvcResultCode()
    {
        $statusNode = $this->_getStatusNode();
        return (string) $statusNode->payment->CVCResultCode['description'];
    }

    /**
     * Retrive advance risk provider from xml
     *
     * @return string
     */
    public function getAdvancedRiskProvider()
    {
        $statusNode = $this->_getStatusNode();
        return (string) $statusNode->payment->riskScore['Provider'];
    }

    /**
     * Retrive advance risk provider id from xml
     *
     * @return string
     */
    public function getAdvancedRiskProviderId()
    {
        $statusNode = $this->_getStatusNode();
        return (string) $statusNode->payment->riskScore['RGID'];
    }

    /**
     * Retrive advance risk provider Threshold from xml
     *
     * @return string
     */
    public function getAdvancedRiskProviderThreshold()
    {
        $statusNode = $this->_getStatusNode();
        return (string) $statusNode->payment->riskScore['tRisk'];
    }

    /**
     * Retrive advance risk provider Score from xml
     *
     * @return string
     */
    public function getAdvancedRiskProviderScore()
    {
        $statusNode = $this->_getStatusNode();
        return (string) $statusNode->payment->riskScore['tScore'];
    }

    /**
     * Retrive advance risk provider final score from xml
     *
     * @return string
     */
    public function getAdvancedRiskProviderFinalScore()
    {
        $statusNode = $this->_getStatusNode();
        return (string) $statusNode->payment->riskScore['finalScore'];
    }

    /**
     * Retrive Payment refusal code from xml
     *
     * @return string
     */
    public function getPaymentRefusalCode()
    {
        $statusNode = $this->_getStatusNode();
        return $statusNode->payment->issueResponseCode['code'] ? : $statusNode->payment->ISO8583ReturnCode['code'];
    }

    /**
     * Retrive Payment refusal Description from xml
     *
     * @return string
     */
    public function getPaymentRefusalDescription()
    {
        $statusNode = $this->_getStatusNode();
        return $statusNode->payment->issueResponseCode['description'] ? :
                $statusNode->payment->ISO8583ReturnCode['description'];
    }

    /**
     * Retrive journal reference from xml
     *
     * @param String $state
     * @return string
     */
    public function getJournalReference($state)
    {
        $statusNode = $this->_getStatusNode();
        $journalNodes = $statusNode->journal;

        foreach ($journalNodes as $journal) {
            if ($journal['journalType'] == $state) {
                $reference = $journal->journalReference['reference'];
                if ($reference) {
                    return $reference->__toString();
                }
            }
        }

        return false;
    }

    /**
     * Retrive full Refund from xml
     *
     * @return string
     */
    public function getFullRefundAmount()
    {
        $statusNode = $this->_getStatusNode();

        foreach ($statusNode->journal as $journal) {
            if ($journal['journalType'] == \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_SENT_FOR_REFUND) {
                foreach ($journal->accountTx as $account) {
                    if ($account['accountType'] == "IN_PROCESS_CAPTURED") {
                        $amount = $account->amount['value'];
                        if ($amount) {
                            return $amount->__toString();
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Retrive Asynchronus Notification from xml
     *
     * @return string
     */
    public function isAsyncNotification()
    {
        return isset($this->_xml->notify);
    }

    /**
     * Tells if this response is a direct reply xml sent from WP server
     *
     * @return bool
     */
    public function isDirectReply()
    {
        return ! $this->isAsyncNotification();
    }

    /**
     * Retrive AAV Addewss Result Code from xml
     *
     * @return string
     */
    public function getAAVAddressResultCode()
    {
        $statusNode = $this->_getStatusNode();
        return (string) $statusNode->payment->AAVAddressResultCode['description'];
    }

    /**
     * Retrive AAV Postcode Result Code from xml
     *
     * @return string
     */
    public function getAAVPostcodeResultCode()
    {
        $statusNode = $this->_getStatusNode();
        return (string) $statusNode->payment->AAVPostcodeResultCode['description'];
    }

    /**
     * Retrive AAV card holder Name Result Code from xml
     *
     * @return string
     */
    public function getAAVCardholderNameResultCode()
    {
        $statusNode = $this->_getStatusNode();
        return (string) $statusNode->payment->AAVCardholderNameResultCode['description'];
    }

    /**
     * Retrive AAV Telephone Result Code from xml
     *
     * @return string
     */
    public function getAAVTelephoneResultCode()
    {
        $statusNode = $this->_getStatusNode();
        return (string) $statusNode->payment->AAVTelephoneResultCode['description'];
    }

    /**
     * Retrive AAV Email Result Code from xml
     *
     * @return string
     */
    public function getAAVEmailResultCode()
    {
        $statusNode = $this->_getStatusNode();
        return (string) $statusNode->payment->AAVEmailResultCode['description'];
    }

    /**
     * Currency
     *
     * @return string
     */
    public function getCurrency()
    {
        $statusNode = $this->_getStatusNode();
        return (string) $statusNode->payment->amount['currencyCode'];
    }
    
    /**
     * NetworkUsed
     *
     * @return string
     */
    public function getNetworkUsed()
    {
        $statusNode = $this->_getStatusNode();
        return (string) $statusNode->payment->primeRoutingResponse->networkUsed;
    }
    
    /**
     * SourceType
     *
     * @return string
     */
    public function getSourceType()
    {
        $statusNode = $this->_getStatusNode();
        if (!empty($statusNode->payment->enhancedAuthResponse)) {
            return (string) $statusNode->payment->enhancedAuthResponse->fundingSource->sourceType;
        }
    }
    
    /**
     * AvailableBalance
     *
     * @return string
     */
    public function getAvailableBalance()
    {
        $statusNode = $this->_getStatusNode();
        if (!empty($statusNode->payment->enhancedAuthResponse)) {
            return (string) $statusNode->payment->enhancedAuthResponse->fundingSource->availableBalance;
        }
    }
    
    /**
     * PrepaidCardType
     *
     * @return string
     */
    public function getPrepaidCardType()
    {
        $statusNode = $this->_getStatusNode();
        if (!empty($statusNode->payment->enhancedAuthResponse)) {
            return (string) $statusNode->payment->enhancedAuthResponse->fundingSource->prepaidCardType;
        }
    }
    
    /**
     * Reloadable
     *
     * @return string
     */
    public function getReloadable()
    {
        $statusNode = $this->_getStatusNode();
        if (!empty($statusNode->payment->enhancedAuthResponse)) {
            return (string) $statusNode->payment->enhancedAuthResponse->fundingSource->reloadable;
        }
    }
    
    /**
     * Get card product type
     *
     * @return string
     */
    public function getCardProductType()
    {
        $statusNode = $this->_getStatusNode();
        return (string) $statusNode->payment->enhancedAuthResponse->cardProductType;
    }
    
    /**
     * Get affluence
     *
     * @return string
     */
    public function getAffluence()
    {
        $statusNode = $this->_getStatusNode();
        return (string) $statusNode->payment->enhancedAuthResponse->affluence;
    }
    
    /**
     * Get Account range id
     *
     * @return string
     */
    public function getAccountRangeId()
    {
        $statusNode = $this->_getStatusNode();
        return (string) $statusNode->payment->enhancedAuthResponse->accountRangeId;
    }
    
    /**
     * Issuer Country
     *
     * @return string
     */
    public function getIssuerCountry()
    {
        $statusNode = $this->_getStatusNode();
        return (string) $statusNode->payment->enhancedAuthResponse->issuerCountry;
    }
    
    /**
     * Get Virtual Account Number
     *
     * @return string
     */
    public function getVirtualAccountNumber()
    {
        $statusNode = $this->_getStatusNode();
        return (string) $statusNode->payment->enhancedAuthResponse->virtualAccountNumber;
    }
    
    /**
     * Get fraudsight message
     *
     * @return string
     */
    public function getFraudsightMessage()
    {
        if (strtoupper($this->getAdvancedRiskProvider()) === 'FRAUDSIGHT') {
            $statusNode = $this->_getStatusNode();
            return (string) $statusNode->payment->riskScore['message'];
        }
    }
    
    /**
     * Get Fraudsight score
     *
     * @return string
     */
    public function getFraudsightScore()
    {
        $statusNode = $this->_getStatusNode();
        if (!empty($statusNode->payment->FraudSight)) {
            return (string) $statusNode->payment->FraudSight['score'];
        }
    }
    
    /**
     * Get fraudsight reason codes
     *
     * @return string
     */
    public function getFraudsightReasonCode()
    {
        $statusNode = $this->_getStatusNode();
        if (!empty($statusNode->payment->FraudSight)) {
            $reasoncodes = $statusNode->payment->FraudSight->reasonCodes->reasonCode;
            $savereasoncode='';
            foreach ($reasoncodes as $key => $reasoncode) {
                    $savereasoncode=$savereasoncode.",".$reasoncode;
            }
            return ltrim($savereasoncode, ",");
        }
    }
}
