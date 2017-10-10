<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Request;

class CurlRequest
{
    private $_handle;
    private $_url;

    public function setUrl($url)
    {
        $this->_url = $url;
        $this->_handle = curl_init($this->_url);
    }

    public function setOption($name, $value)
    {
        return curl_setopt($this->_handle, $name, $value);
    }

    public function execute()
    {
        return curl_exec($this->_handle);
    }

    public function getInfo($opt=null)
    {
       return curl_getinfo($this->_handle, $opt);
    }

    public function getError()
    {
        return curl_error($this->_handle);
    }

    public function close()
    {
        return curl_close($this->_handle);
    }
}
