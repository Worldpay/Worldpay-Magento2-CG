<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Config\Source;

class ChallengeWindowSize extends \Magento\Framework\App\Config\Value
{
    /**
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
