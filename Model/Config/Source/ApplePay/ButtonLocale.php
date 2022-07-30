<?php
namespace Sapient\Worldpay\Model\Config\Source\ApplePay;

class ButtonLocale implements \Magento\Framework\Data\OptionSourceInterface
{
 /**
  * Get button locale options
  */
    public function toOptionArray()
    {
        return [
            ['value' => 'en-GB', 'label' => __('en-GB')],
            ['value' => 'ar-AB', 'label' => __('ar-AB')],
            ['value' => 'ca-ES', 'label' => __('ca-ES')],
            ['value' => 'cs-CZ', 'label' => __('cs-CZ')],
            ['value' => 'da-DK', 'label' => __('da-DK')],
            ['value' => 'de-DE', 'label' => __('de-DE')],
            ['value' => 'el-GR', 'label' => __('el-GR')],
            ['value' => 'en-AU', 'label' => __('en-AU')],
            ['value' => 'en-US', 'label' => __('en-US')],
            ['value' => 'es-ES', 'label' => __('es-ES')],
            ['value' => 'es-MX', 'label' => __('es-MX')],
            ['value' => 'fi-FI', 'label' => __('fi-FI')],
            ['value' => 'fr-CA', 'label' => __('fr-CA')],
            ['value' => 'fr-FR', 'label' => __('fr-FR')],
            ['value' => 'he-IL', 'label' => __('he-IL')],
            ['value' => 'hi-IN', 'label' => __('hi-IN')],
            ['value' => 'hr-HR', 'label' => __('hr-HR')],
            ['value' => 'hu-HU', 'label' => __('hu-HU')],
            ['value' => 'id-ID', 'label' => __('id-ID')],
            ['value' => 'it-IT', 'label' => __('it-IT')],
            ['value' => 'ja-JP', 'label' => __('ja-JP')],
            ['value' => 'ko-KR', 'label' => __('ko-KR')],
            ['value' => 'ms-MY', 'label' => __('ms-MY')],
            ['value' => 'nb-NO', 'label' => __('nb-NO')],
            ['value' => 'nl-NL', 'label' => __('nl-NL')],
            ['value' => 'pl-PL', 'label' => __('pl-PL')],
            ['value' => 'pt-BR', 'label' => __('pt-BR')],
            ['value' => 'pt-PT', 'label' => __('pt-PT')],
            ['value' => 'ro-RO', 'label' => __('ro-RO')],
            ['value' => 'ru-RU', 'label' => __('ru-RU')],
            ['value' => 'sk-SK', 'label' => __('sk-SK')],
            ['value' => 'sv-SE', 'label' => __('sv-SE')],
            ['value' => 'th-TH', 'label' => __('th-TH')],
            ['value' => 'tr-TR', 'label' => __('tr-TR')],
            ['value' => 'uk-UA', 'label' => __('uk-UA')],
            ['value' => 'vi-VN', 'label' => __('vi-VN')],
            ['value' => 'zh-CN', 'label' => __('zh-CN')],
            ['value' => 'zh-HK', 'label' => __('zh-HK')],
            ['value' => 'zh-HK', 'label' => __('zh-HK')],
        ];
    }
}
