<?php
namespace Sapient\Worldpay\Model\Config\Source\GooglePay;

class ButtonLocale implements \Magento\Framework\Data\OptionSourceInterface
{
 /**
  * Get button locale options
  */
    public function toOptionArray()
    {
        return [
            ['value' => 'en', 'label' => __('en')],
            ['value' => 'ar', 'label' => __('ar')],
            ['value' => 'bg', 'label' => __('bg')],
            ['value' => 'ca', 'label' => __('ca')],
            ['value' => 'cs', 'label' => __('cs')],
            ['value' => 'da', 'label' => __('da')],
            ['value' => 'de', 'label' => __('de')],
            ['value' => 'el', 'label' => __('el')],
            ['value' => 'es', 'label' => __('es')],
            ['value' => 'et', 'label' => __('et')],
            ['value' => 'fi', 'label' => __('fi')],
            ['value' => 'fr', 'label' => __('fr')],
            ['value' => 'hr', 'label' => __('hr')],
            ['value' => 'id', 'label' => __('id')],
            ['value' => 'it', 'label' => __('it')],
            ['value' => 'ja', 'label' => __('ja')],
            ['value' => 'ko', 'label' => __('ko')],
            ['value' => 'ms', 'label' => __('ms')],
            ['value' => 'nl', 'label' => __('nl')],
            ['value' => 'no', 'label' => __('no')],
            ['value' => 'pl', 'label' => __('pl')],
            ['value' => 'pt', 'label' => __('pt')],
            ['value' => 'ru', 'label' => __('ru')],
            ['value' => 'sk', 'label' => __('sk')],
            ['value' => 'sl', 'label' => __('sl')],
            ['value' => 'sr', 'label' => __('sr')],
            ['value' => 'sv', 'label' => __('sv')],
            ['value' => 'th', 'label' => __('th')],
            ['value' => 'tr', 'label' => __('tr')],
            ['value' => 'uk', 'label' => __('uk')],
            ['value' => 'zh', 'label' => __('zh')]
        ];
    }
}
