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
     * Constructor
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Sapient\Worldpay\Model\ResourceModel\SavedToken $entityResource,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->entityResource = $entityResource;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /** @var \Sapient\Worldpay\Model\ResourceModel\SavedToken */
    private $entityResource;

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
     * @return $this|null
     */
    public function loadByTokenCode($tokenCode)
    {
        if (!$tokenCode) {
            return;
        }
        $id = $this->entityResource->loadByTokenCode($tokenCode);
        $this->entityResource->load($this, $id);
        return $this;
    }

    /**
     * Load worldpay Storedcredentials Details
     *
     * @param int $transactionIdentifier
     * @return $this|null
     */
    public function loadByStoredCredentials($transactionIdentifier)
    {
        if (!$transactionIdentifier) {
            return;
        }
        $id = $this->entityResource->loadByStoredCredentials($transactionIdentifier);
        $this->entityResource->load($this, $id);
        return $this;
    }
}
