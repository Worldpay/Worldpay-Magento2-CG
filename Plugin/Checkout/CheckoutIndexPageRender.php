<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Plugin\Checkout;

use Magento\Framework\View\Page\Config;
use Sapient\Worldpay\Helper\Data;

class CheckoutIndexPageRender
{
    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    public $worldpayHelper;

    /**
     * @param Data $worldpayHelper
     */
    public function __construct(
        Data $worldpayHelper
    ) {
        $this->worldpayHelper = $worldpayHelper;
    }

    /**
     * Get the order date details
     *
     * @param Onepage $subject
     * @param string $result
     *
     * @return string
     */
    public function afterToHtml(\Magento\Checkout\Block\Onepage $subject, $result)
    {
        $isworldpayEnable = $this->worldpayHelper->isWorldPayEnable();
        if ($isworldpayEnable) {
            $cssFile = '<link rel="stylesheet" type="text/css" media="all"
            href="' . $subject->getViewFileUrl('Sapient_Worldpay::css/worldpay.css') . '" />';
            return $result . $cssFile;
        } else {
            return $result;
        }
    }
}
