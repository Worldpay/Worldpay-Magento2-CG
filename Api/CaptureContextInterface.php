<?php

namespace Sapient\Worldpay\Api;

interface CaptureContextInterface
{

    /**
     * Generate a capture context JWT token for Click-to-Pay authentication.
     *
     * @return string|null
     */
    public function captureContext(): ?string;
}
