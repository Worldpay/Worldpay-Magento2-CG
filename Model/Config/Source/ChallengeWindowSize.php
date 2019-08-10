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
            ['value' => 'None', 'label' => __('None')],
            ['value' => 'fullPage', 'label' => __('Full Page')],
            ['value' => '250x400', 'label' => __('250x400')],
            ['value' => '390x400', 'label' => __('390x400')],
            ['value' => '600x400', 'label' => __('600x400')]
        ];
    }

}