<?php
/**
 * Copyright © Sapient, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sapient\Worldpay\Model\Token;

use \Magento\Framework\Phrase;

/**
 * Exception for Security violation cases
 *
 * @api
 * @since 100.1.0
 */
class AccessDeniedException extends \Magento\Framework\Exception\LocalizedException
{
    /**
     * @param \Magento\Framework\Phrase $phrase
     * @param \Exception $cause
     * @param int $code
     */
    public function __construct(Phrase $phrase = null, \Exception $cause = null, $code = 0)
    {
        if ($phrase === null) {
            $phrase = new Phrase('Access Denied');
        }
        parent::__construct($phrase, $cause, $code);
    }
}
