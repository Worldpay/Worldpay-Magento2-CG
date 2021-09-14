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

    /**
     * Config constructor
     * @param string $successURL
     * @param string $cancelURL
     * @param string $pendingURL
     * @param string $errorURL
     * @param string $failureURL
     */
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
     * Function to return success url
     *
     * @return string
     */
    public function getSuccessURL()
    {
        return $this->successURL;
    }

    /**
     * Function to return cancel url
     *
     * @return string
     */
    public function getCancelURL()
    {
        return $this->cancelURL;
    }

    /**
     * Function to return pending url
     *
     * @return string
     */
    public function getPendingURL()
    {
        return $this->pendingURL;
    }

    /**
     * Function to return error url
     *
     * @return string
     */
    public function getErrorURL()
    {
        return $this->errorURL;
    }

    /**
     * Function to return failure url
     *
     * @return string
     */
    public function getFailureURL()
    {
        return $this->failureURL;
    }
}
