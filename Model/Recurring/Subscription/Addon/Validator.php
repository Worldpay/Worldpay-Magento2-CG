<?php

/**
 * Copyright © 2020 Worldpay. All rights reserved.
 */

namespace Sapient\Worldpay\Model\Recurring\Subscription\Addon;

class Validator extends \Magento\Framework\Validator\AbstractValidator
{
    const CODE_MAX_LENGTH = 25;
    const NAME_MAX_LENGTH = 100;

    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * If $value fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @param  mixed $value
     * @return boolean
     */
    public function isValid($value)
    {
        $messages = [];

        /* Code is required and must not exceed 25 characters */
        if (!\Zend_Validate::is(trim($value->getCode()), 'NotEmpty')) {
            $this->addErrorMessage($messages, 'The value "%fieldName" is a required field.', ['fieldName' => 'Code']);
        }

        if (!\Zend_Validate::is(trim($value->getCode()), 'StringLength', ['min' => 0, 'max' => self::CODE_MAX_LENGTH])) {
            $this->addErrorMessage(
                $messages,
                'The length of value "%fieldName" must not exceed %maxValue.',
                ['fieldName' => 'Code', 'maxValue' => self::CODE_MAX_LENGTH]
            );
        }

        /* Name is required and must not exceed 100 characters */
        if (!\Zend_Validate::is(trim($value->getName()), 'NotEmpty')) {
            $this->addErrorMessage(
                $messages,
                'The value "%fieldName" is a required field.',
                ['fieldName' => 'Name']
            );
        }

        if (!\Zend_Validate::is(trim($value->getName()), 'StringLength', ['min' => 0, 'max' => self::NAME_MAX_LENGTH])) {
            $this->addErrorMessage(
                $messages,
                'The length of value "%fieldName" must not exceed %maxValue.',
                ['fieldName' => 'Name', 'maxValue' => self::NAME_MAX_LENGTH]
            );
        }

        /* Amount is required */
        if (!\Zend_Validate::is(trim($value->getAmount()), 'NotEmpty')) {
            $this->addErrorMessage($messages, 'The value "%fieldName" is a required field.', ['fieldName' => 'Amount']);
        }

        /* Start Date is required */
        if (!\Zend_Validate::is(trim($value->getStartDate()), 'NotEmpty')) {
            $this->addErrorMessage($messages, 'The value "%fieldName" is a required field.', ['fieldName' => 'Start Date']);
        }

        /* End Date is required */
        if (!\Zend_Validate::is(trim($value->getEndDate()), 'NotEmpty')) {
            $this->addErrorMessage(
                $messages,
                'The value "%fieldName" is a required field.',
                ['fieldName' => 'End Date']
            );
        }

        $this->_addMessages($messages);
        return empty($messages);
    }

    /**
     * Format error message
     *
     * @param string[] $messages
     * @param string $message
     * @param array $params
     * @return void
     */
    protected function addErrorMessage(&$messages, $message, $params)
    {
        $messages[$params['fieldName']] = __($message, $params);
    }
}
