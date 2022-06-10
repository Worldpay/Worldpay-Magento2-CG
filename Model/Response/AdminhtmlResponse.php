<?php
/**
 * Copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Response;

class AdminhtmlResponse extends \Sapient\Worldpay\Model\Response\ResponseAbstract
{
   
   /**
    * [parseRefundResponse description]
    *
    * @param  [type] $xml [description]
    * @return [type]      [description]
    */
    public function parseRefundResponse($xml)
    {
        $document = new \SimpleXmlElement($xml);
        return $document;
    }
    /**
     * [parseInquiryResponse description]
     *
     * @param  [type] $xml [description]
     * @return [type]      [description]
     */
    public function parseInquiryResponse($xml)
    {
        $document = new \SimpleXmlElement($xml);
        return $document;
    }
    /**
     * [parseVoidSaleRespone description]
     *
     * @param  [type] $xml [description]
     * @return [type]      [description]
     */
    public function parseVoidSaleRespone($xml)
    {
        $document = new \SimpleXmlElement($xml);
        return $document;
    }
    /**
     * [parseCancelOrderRespone description]
     *
     * @param  [type] $xml [description]
     * @return [type]      [description]
     */
    public function parseCancelOrderRespone($xml)
    {
        $document = new \SimpleXmlElement($xml);
        return $document;
    }
}
