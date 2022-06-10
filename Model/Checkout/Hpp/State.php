<?php
/**
 *
 * Copyright © 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Checkout\Hpp;

use Exception;

class State
{
    public const SESSION_KEY_STATE = 'worldpay_hpp_state';
    public const SESSION_KEY_URL = 'worldpay_hpp_redirect_url';

    /**
     * State constructor
     *
     * @param \Magento\Checkout\Model\Session $checkoutsession
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutsession
    ) {

        $this->session = $checkoutsession;
    }

    /**
     * IsUninitialised
     */
    public function isUninitialised()
    {
        return !$this->session->hasData(self::SESSION_KEY_STATE);
    }

    /**
     * IsInitialised
     */
    public function isInitialised()
    {
        return $this->session->getData(self::SESSION_KEY_STATE) === 'initialised';
    }

    /**
     * Move Hosted Payment into initialised state
     *
     * @param string $redirectUrl URL extracted from XML redirect order request
     *
     * @return $this
     */
    public function init($redirectUrl)
    {
        if (!$this->isUninitialised()) {
            throw new \DomainException('Hosted Payment has been already initialised.');
        }
      
        $this->session->setData(self::SESSION_KEY_URL, $redirectUrl);
        $this->session->setData(self::SESSION_KEY_STATE, 'initialised');

        return $this;
    }

    /**
     * Move Hosted Payment into uninitialised state
     *
     * @return $this
     */
    public function finish()
    {
        $this->validateInitializesState();

        $this->reset();

        return $this;
    }

    /**
     * Reset state to uninitialised
     *
     * @return $this
     */
    public function reset()
    {
        $this->session->unsetData(self::SESSION_KEY_URL);
        $this->session->unsetData(self::SESSION_KEY_STATE);

        return $this;
    }

    /**
     * Returns the XML redirect URL
     *
     * Available in initialized state only.
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        $this->validateInitializesState();

        return $this->session->getData(self::SESSION_KEY_URL);
    }

    /**
     * ValidateInitializesState
     */
    private function validateInitializesState()
    {
        if (!$this->isInitialised()) {
            throw new \DomainException('Hosted Payment has not been initialised.');
        }
    }
}
