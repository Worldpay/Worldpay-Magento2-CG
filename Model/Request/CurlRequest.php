<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Request;

/**
 * set curl param request
 */
class CurlRequest
{
    private $_handle;
    private $_url;

    public function setUrl($url)
    {
        $this->_url = $url;
        // @codingStandardsIgnoreLine
        $this->_handle = curl_init($this->_url);
    }

    public function setOption($name, $value)
    {
        // @codingStandardsIgnoreLine
        return curl_setopt($this->_handle, $name, $value);
    }

    public function execute()
    {
        // @codingStandardsIgnoreLine
        return curl_exec($this->_handle);
    }

    public function getInfo($opt = null)
    {
        // @codingStandardsIgnoreLine
        return curl_getinfo($this->_handle, $opt);
    }

    public function getError()
    {
        // @codingStandardsIgnoreLine
        return curl_error($this->_handle);
    }

    public function close()
    {
        // @codingStandardsIgnoreLine
        return curl_close($this->_handle);
    }
}
