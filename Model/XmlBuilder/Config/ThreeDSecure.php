<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\XmlBuilder\Config;

/**
 * Get ThreeDSecure Configuration
 */
class ThreeDSecure
{
    /**
     * @var bool
     */
    private $isDynamic3D = false;
    /**
     * @var bool
     */
    private $is3DSecure = false;

    /**
     * @param bool $isDynamic3D
     * @param bool $is3DSecure
     */
    public function __construct($isDynamic3D = false, $is3DSecure = false)
    {
        $this->isDynamic3D = (bool)$isDynamic3D;
        $this->is3DSecure = (bool)$is3DSecure;
    }

    /**
     * Check if dynamic 3d is enabled?
     *
     * @return bool
     */
    public function isDynamic3DEnabled()
    {
        return $this->isDynamic3D;
    }

    /**
     * Check if 3ds is enabled?
     *
     * @return bool
     */
    public function is3DSecureCheckEnabled()
    {
        return $this->is3DSecure;
    }
}
