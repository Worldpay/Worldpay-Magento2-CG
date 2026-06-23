<?php
namespace Sapient\Worldpay\Model\Clicktopay;

class SignatureGenerator
{
    public function generate(
        string $keyId,
        string $secretKey,
        string $merchantId,
        string $endpoint,
        string $httpMethod,
        string $date,
        string $digest
    ): string{
        $host = strtolower(parse_url($endpoint, PHP_URL_HOST));
        $resourcePath = parse_url($endpoint, PHP_URL_PATH);
        $method = strtolower($httpMethod);
        $signatureHeaders = [
            '(request-target): ' . $method . ' ' . $resourcePath,
            'host: ' . $host,
            'date: ' . $date,
            'digest: ' . $digest,
            'v-c-merchant-id: ' . $merchantId
        ];
        $stringToSign = implode("\n", $signatureHeaders);
        $signature = base64_encode(hash_hmac('sha256', $stringToSign, $secretKey, true));
        return sprintf(
            'keyid="%s", algorithm="HmacSHA256", headers="(request-target) host date digest v-c-merchant-id", signature="%s"',
            $keyId,
            $signature
        );
    }
}
