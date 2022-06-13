<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Config\Source;

class AuthenticationMethods extends \Magento\Framework\App\Config\Value
{
  /**
   * ToOption Array
   *
   * @return array
   */
    public function toOptionArray()
    {

        return [
            ['value' => 'None', 'label' => __('None')],
            ['value' => 'guestCheckout', 'label' => __('Guest Checkout')],
            ['value' => 'localAccount', 'label' => __('Local Account')],
            ['value' => 'federatedAccount', 'label' => __('Federated Account')],
            ['value' => 'fidoAuthenticator', 'label' => __('Fido Authenticator')],
            ['value' => 'issuerCredentials', 'label' => __('Issuer Credentials')],
            ['value' => 'thirdPartyAuthentication', 'label' => __('ThirdParty Authentication')]
        ];
    }
}
