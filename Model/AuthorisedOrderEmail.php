<?php

namespace Sapient\Worldpay\Model;

use Magento\Sales\Model\AbstractModel;

class AuthorisedOrderEmail extends AbstractModel
{
    protected function _construct(): void
    {
        $this->_init(ResourceModel\AuthorisedOrderEmail::class);
    }
}
