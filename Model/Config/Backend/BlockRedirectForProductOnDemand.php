<?php

namespace Sapient\Worldpay\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

class BlockRedirectForProductOnDemand extends Value
{
    public function beforeSave(): void
    {
        $newProductOnDemand = $this->getData('groups/product_on_demand/fields/enabled/value');
        $newIntegrationMode = $this->getData('groups/cc_config/fields/integration_mode/value');
        $currentProductOnDemandEnabled = $this->_config->getValue('worldpay/product_on_demand/enabled', $this->getScope(), $this->getScopeId());
        $currentIntegrationMode = $this->_config->getValue('worldpay/cc_config/integration_mode', $this->getScope(), $this->getScopeId());

        if (
            ($currentProductOnDemandEnabled == '0' && $newProductOnDemand === '1' && $newIntegrationMode === 'redirect' && $currentIntegrationMode === 'redirect')
            || ($currentIntegrationMode == 'direct' && $newIntegrationMode === 'redirect' && $currentProductOnDemandEnabled == '1' && $newProductOnDemand === '1')
            || ($currentProductOnDemandEnabled == '0' && $newProductOnDemand === '1' && $currentIntegrationMode == 'direct' && $newIntegrationMode === 'redirect')
        ) {
            throw new LocalizedException(__('Product on Demand cannot be enabled when Integration Mode is set to Redirect.'));
        }

        if (
            $newIntegrationMode === 'redirect' && $currentIntegrationMode === 'redirect'
            && $currentProductOnDemandEnabled == '1' && $newProductOnDemand === '1'
        ) {
            throw new LocalizedException(__('Product on Demand cannot be enabled when Integration Mode is set to Redirect. Please change configurations.'));
        }

        parent::beforeSave();
    }
}
