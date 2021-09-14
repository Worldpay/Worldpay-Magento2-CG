<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Config\Source;

class ChallengeWindowSize extends \Magento\Framework\App\Config\Value
{
    /**
     * Challenge window size for 3ds2
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'fullPage', 'label' => __('Full Page')],
            ['value' => 'iframe', 'label' => __('Iframe')]
        ];
    }
}
