<?php
namespace Sapient\Worldpay\Model\XmlBuilder;

class DirectOrderToken extends AbstractXmlBuilder
{
    /**
     * Build a standalone payment token request.
     *
     * @param array $parameters
     * @return \SimpleXMLElement
     */
    public function build(array $parameters)
    {
        $xml = new \SimpleXMLElement(self::ROOT_ELEMENT);
        $xml['version'] = '1.4';
        $xml['merchantCode'] = $parameters['merchantCode'];
        $token = $xml->addChild('submit')->addChild('paymentTokenCreate');
        $this->_addCDATA($token->addChild('authenticatedShopperID'), (string) $parameters['authenticatedShopperID']);
        $createToken = $token->addChild('createToken');
        $createToken['tokenScope'] = $parameters['tokenScope'];
        $this->_addCDATA($createToken->addChild('tokenEventReference'), $parameters['tokenEventReference']);
        $this->_addCDATA($createToken->addChild('tokenReason'), $parameters['tokenReason']);

        $details = $parameters['paymentDetails'];
        if (isset($details['encryptedData'])) {
            $this->_addCDATA($token->addChild('CSE-DATA')->addChild('encryptedData'), $details['encryptedData']);
        } else {
            $card = $token->addChild('paymentInstrument')->addChild('cardDetails');
            $this->_addCDATA($card->addChild('cardNumber'), $details['cardNumber']);
            $date = $card->addChild('expiryDate')->addChild('date');
            $date['month'] = sprintf('%02d', $details['expiryMonth']);
            $date['year'] = $details['expiryYear'];
            $this->_addCDATA($card->addChild('cardHolderName'), $details['cardHolderName']);
            if (isset($details['cvc'])) {
                $this->_addCDATA($card->addChild('cvc'), $details['cvc']);
            }
            $address = $parameters['customerAddress'];
            $street = $address['street'];
            $this->_addAddressElement($card->addChild('cardAddress'), [
                'firstName' => $address['firstname'],
                'lastName' => $address['lastname'],
                'street' => is_array($street) ? implode(' ', $street) : $street,
                'postalCode' => $address['postcode'],
                'city' => $address['city'],
                'state' => $address['region']['region'] ?? null,
                'countryCode' => $address['country_id'],
                'telephoneNumber' => $address['telephone'] ?? null
            ]);
        }
        return $xml;
    }
}
