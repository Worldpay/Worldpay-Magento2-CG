<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Resource Model
 */
class SavedToken extends AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Sapient\Worldpay\Model\ResourceModel\SavedToken::class);
    }

    /**
     * Load worldpay token Details
     *
     * @param string $tokenCode
     * @return \Sapient\Worldpay\Model\SavedToken
     */
    public function loadByTokenCode($tokenCode)
    {
        if (!$tokenCode) {
            return;
        }
        $id = $this->getResource()->loadByTokenCode($tokenCode);
        return $this->load($id);
    }

    /**
     * Load worldpay Storedcredentials Details
     *
     * @param int $transactionIdentifier
     * @return Sapient\Worldpay\Model\SavedToken
     */
    public function loadByStoredCredentials($transactionIdentifier)
    {
        if (!$transactionIdentifier) {
            return;
        }
        $id = $this->getResource()->loadByStoredCredentials($transactionIdentifier);
        return $this->load($id);
    }
}
