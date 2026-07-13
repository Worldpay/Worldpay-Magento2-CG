<?php

namespace Sapient\Worldpay\Api;

interface TransientTokenInterface
{
    /**
     * Validate and decode a transient token JWT from Click-to-Pay.
     *
     * @param string $transientToken The transient token JWT to validate
     * @return string JSON encoded result with decoded payload or error
     */
    public function validateToken(string $transientToken): string;
}

