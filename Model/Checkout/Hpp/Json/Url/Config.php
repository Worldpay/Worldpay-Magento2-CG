<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Checkout\Hpp\Json\Url;

use Exception;

class Config
{
    /** @var string $successURL The URL to redirect the shopper to when the payment is successfully completed. */
    private $successURL;

    /** @var string The URL to redirect the shopper to if the payment is cancelled. */
    private $cancelURL;

    /** @var string The URL to redirect the shopper to if a pending payment status is returned. */
    private $pendingURL;

    /** @var string The URL to redirect the shopper if there is an unrecoverable error during the payment processing. */
    private $errorURL;

    /** @var string $failureURL The URL to redirect the shopper to if the payment fails. */
    private $failureURL;

    public function __construct($successURL, $cancelURL, $pendingURL, $errorURL, $failureURL = null)
    {

        if (filter_var($successURL, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('successURL is not a valid URL.');
        }
        if (filter_var($cancelURL, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('cancelURL is not a valid URL.');
        }
        if (filter_var($pendingURL, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('pendingURL is not a valid URL.');
        }
        if (filter_var($errorURL, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('errorURL is not a valid URL.');
        }
        if (!empty($failureURL) && filter_var($failureURL, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('failureURL is not a valid URL.');
        }

        $this->successURL = $successURL;
        $this->cancelURL = $cancelURL;
        $this->pendingURL = $pendingURL;
        $this->errorURL = $errorURL;
        $this->failureURL = $failureURL;
    }

    /**
     * @return string
     */
    public function getSuccessURL()
    {
        return $this->successURL;
    }

    /**
     * @return string
     */
    public function getCancelURL()
    {
        return $this->cancelURL;
    }

    /**
     * @return string
     */
    public function getPendingURL()
    {
        return $this->pendingURL;
    }

    /**
     * @return string
     */
    public function getErrorURL()
    {
        return $this->errorURL;
    }

    /**
     * @return string
     */
    public function getFailureURL()
    {
        return $this->failureURL;
    }
}
