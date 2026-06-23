<?php
namespace Sapient\Worldpay\Model\Clicktopay;

use Sapient\Worldpay\Helper\CurlHelper;

class ApiClient
{
    /**
     * @var CurlHelper
     */
    protected $curlHelper;

    public function __construct(CurlHelper $curlHelper)
    {
        $this->curlHelper = $curlHelper;
    }

    public function post(string $endpoint, array $payload, array $headers): ?string
    {
        try {
            $jsonPayload = json_encode($payload);
            $response = $this->curlHelper->sendCurlRequest(
                $endpoint,
                [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $jsonPayload,
                    CURLOPT_HTTPHEADER => $headers
                ]
            );

            if (!$response) {
                return null;
            }

            return $response;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function get(string $endpoint, array $headers = []): ?string
    {
        try {
            $response = $this->curlHelper->sendGetCurlRequest(
                $endpoint,
                [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTPHEADER => $headers
                ],
                $headers
            );

            if (!$response) {
                return null;
            }

            return $response;
        } catch (\Exception $e) {
            return null;
        }
    }
}
