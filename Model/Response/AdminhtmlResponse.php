<?php
/**
 * Copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Response;

class AdminhtmlResponse extends \Sapient\Worldpay\Model\Response\ResponseAbstract
{
    
    public function parseRefundResponse($xml)
    {
        $document = new \SimpleXmlElement($xml);
        return $document;
    }

    public function parseInquiryResponse($xml)
    {
        $document = new \SimpleXmlElement($xml);
        return $document;
    }
    
    public function parseVoidSaleRespone($xml)
    {
        $document = new \SimpleXmlElement($xml);
        return $document;
    }
    
    public function parseCancelOrderRespone($xml)
    {
        $document = new \SimpleXmlElement($xml);
        return $document;
    }
}
