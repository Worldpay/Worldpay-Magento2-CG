<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Block;

use Magento\Framework\Serialize\SerializerInterface;

class Savedcard extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Sapient\Worldpay\Model\SavedTokenFactory
     */
    protected $_savecard;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
     /**
      * @var SerializerInterface
      */
    private $serializer;
    /**
     * constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Sapient\Worldpay\Model\SavedTokenFactory $savecard
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Sapient\Worldpay\Helper\Data $worldpayhelper
     * @param SerializerInterface $serializer
     * @param \Magento\Customer\Helper\Session\CurrentCustomerAddress $currentCustomerAddress
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Sapient\Worldpay\Model\SavedTokenFactory $savecard,
        \Magento\Customer\Model\Session $customerSession,
        \Sapient\Worldpay\Helper\Data $worldpayhelper,
        SerializerInterface $serializer,
        \Magento\Customer\Helper\Session\CurrentCustomerAddress $currentCustomerAddress,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        array $data = []
    ) {
        $this->_savecard = $savecard;
        $this->_customerSession = $customerSession;
        $this->worlpayhelper = $worldpayhelper;
        $this->serializer = $serializer;
        $this->currentCustomerAddress = $currentCustomerAddress;
        $this->_addressConfig = $addressConfig;
        $this->addressMapper = $addressMapper;
           parent::__construct($context, $data);
    }
    
    /**
     * Get order id column value
     *
     * @return string
     */
   
    public function isIAVEnabled()
    {
        return $this->worlpayhelper->isIAVEnabled();
    }
    
    /**
     * Get order id column value
     *
     * @return string
     */
   
    public function getAddNewCardLabel()
    {
            return $this->getUrl('worldpay/savedcard/addnewcard', ['_secure' => true]);
    }
    /**
     * Check Billing address
     *
     * @return string
     */

    public function ifBillingAddressPresent()
    {
        $address = $this->currentCustomerAddress->getDefaultBillingAddress();
        if ($address) {
            return true;
        }
        return false;
    }
    
    /**
     * Render an address as HTML and return the result
     *
     * @param AddressInterface $address
     * @return string
     */
    protected function getPrimaryBillingAddressHtml()
    {
        /** @var \Magento\Customer\Block\Address\Renderer\RendererInterface $renderer */
        $address = $this->currentCustomerAddress->getDefaultBillingAddress();
        if ($address) {
            $renderer = $this->_addressConfig->getFormatByCode('html')->getRenderer();
            return $renderer->renderArray($this->addressMapper->toFlatArray($address));
        } else {
            return $this->escapeHtml(__('You have not set a default billing address.'));
        }
    }

    /**
     * Get Saved card data
     *
     * @return bool|\Sapient\Worldpay\Model\ResourceModel\SavedToken\Collection
     */
    public function getSavedCard()
    {
        if (!($customerId = $this->_customerSession->getCustomerId())) {
            return false;
        }
        $merchantTokenEnabled = $this->worlpayhelper->getMerchantTokenization();
        $tokenType = $merchantTokenEnabled ? 'merchant' : 'shopper';
        return $savedCardCollection = $this->_savecard->create()->getCollection()
                                    ->addFieldToSelect(['card_brand','card_number',
                                        'cardholder_name','card_expiry_month','card_expiry_year',
                                        'transaction_identifier', 'token_code'])
                                    ->addFieldToFilter('customer_id', ['eq' => $customerId])
                                    ->addFieldToFilter('token_type', ['eq' => $tokenType]);
    }
    /**
     * Check Account Label
     *
     * @param string $labelCode
     * @return string
     */

    public function getMyAccountLabels($labelCode)
    {
        $accdata = $this->serializer->unserialize($this->worlpayhelper->getMyAccountLabels());
        if (is_array($accdata) || is_object($accdata)) {
            foreach ($accdata as $key => $valuepair) {
                if ($key == $labelCode) {
                    return $valuepair['wpay_custom_label']?$valuepair['wpay_custom_label']:
                        $valuepair['wpay_label_desc'];
                }
            }
        }
    }
    /**
     * Check checkout label
     *
     * @param string $labelCode
     * @return string
     */

    public function getCheckoutLabels($labelCode)
    {
        $accdata = $this->serializer->unserialize($this->worlpayhelper->getCheckoutLabels());
        if (is_array($accdata) || is_object($accdata)) {
            foreach ($accdata as $key => $valuepair) {
                if ($key == $labelCode) {
                    return $valuepair['wpay_custom_label']?$valuepair['wpay_custom_label']:
                        $valuepair['wpay_label_desc'];
                }
            }
        }
    }

   /**
    * Delet tokenization Url
    *
    * @param \Sapient\Worldpay\Model\SavedToken $saveCard
    * @return string
    */
    public function getDeleteUrl($saveCard)
    {
        return $this->getUrl('worldpay/savedcard/delete', ['id' => $saveCard->getId()]);
    }

    /**
     * Edite Tokenization url
     *
     * @param \Sapient\Worldpay\Model\SavedToken $saveCard
     * @return string
     */
    public function getEditUrl($saveCard)
    {
        return $this->getUrl('worldpay/savedcard/edit', ['id' => $saveCard->getId()]);
    }
}
