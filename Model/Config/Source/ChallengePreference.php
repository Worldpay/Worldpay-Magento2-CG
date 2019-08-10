<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Config\Source;

class ChallengePreference extends \Magento\Framework\App\Config\Value
{
    /**
     * @return array
     */
    public function toOptionArray()
    {

        return [
            ['value' => 'None', 'label' => __('None')],
            ['value' => 'noPreference', 'label' => __('No Preference')],
            ['value' => 'noChallengeRequested', 'label' => __('No Challenge Requested')],
            ['value' => 'challengeRequested', 'label' => __('Challenge Requested')],
            ['value' => 'challengeMandated', 'label' => __('Challenge Mandated')]
        ];
    }

}