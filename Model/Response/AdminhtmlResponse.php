<?php
/**
 * Copyright © 2017 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Sapient\Worldpay\Model\Response;

class AdminhtmlResponse extends \Sapient\Worldpay\Model\Response\ResponseAbstract
{
    /**
     * Parse refund response
     *
     * @param string $xml
     * @return \SimpleXMLElement
     */
    public function parseRefundResponse($xml)
    {
        $document = new \SimpleXmlElement($xml);
        return $document;
    }

    /**
     * Parse inquiry response
     *
     * @param string $xml
     * @return \SimpleXMLElement
     */
    public function parseInquiryResponse($xml)
    {
        $document = new \SimpleXmlElement($xml);
        return $document;
    }
    
    /**
     * Parse void sale response
     *
     * @param string $xml
     * @return \SimpleXMLElement
     */
    public function parseVoidSaleRespone($xml)
    {
        $document = new \SimpleXmlElement($xml);
        return $document;
    }
    
    /**
     * Parse cancel order response
     *
     * @param string $xml
     * @return \SimpleXMLElement
     */
    public function parseCancelOrderRespone($xml)
    {
        $document = new \SimpleXmlElement($xml);
        return $document;
    }
}
