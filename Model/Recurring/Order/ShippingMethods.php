<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Recurring\Order;

use Sapient\Worldpay\Api\RecurringShippingMethodInterface;

use Magento\Quote\Model\Quote\Address\RateRequestFactory;
use Magento\Quote\Model\Quote\Address\RateCollectorInterfaceFactory;
use Magento\Customer\Api\AddressRepositoryInterface;

class ShippingMethods implements RecurringShippingMethodInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    
    /**
     * @var OrderFactory $orderFactory
     */
    protected $orderFactory;

    /**
     * @var RateRequestFactory $rateRequestFactory
     */
    protected $rateRequestFactory;

    /**
     * @var RateCollectorInterfaceFactory $rateCollector
     */
    protected $rateCollector;

    /**
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * @var AddressRepositoryInterface $addressRepository
     */
    protected $addressRepository;

    /**
     * @var CartRepositoryInterface $cartRepository
     */
    protected $cartRepository;

    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $wplogger;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param RateRequestFactory $rateRequestFactory
     * @param RateCollectorInterfaceFactory $rateCollector
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param AddressRepositoryInterface $addressRepository
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     */

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        RateRequestFactory $rateRequestFactory,
        RateCollectorInterfaceFactory $rateCollector,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        AddressRepositoryInterface $addressRepository,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->orderFactory = $orderFactory;
        $this->rateRequestFactory = $rateRequestFactory;
        $this->rateCollector = $rateCollector;
        $this->storeManager = $storeManager;
        $this->addressRepository = $addressRepository;
        $this->cartRepository = $cartRepository;
        $this->wplogger = $wplogger;
    }

    /**
     * Get Original IncrementId
     *
     * @param int $orderIncrementId
     *
     * @return object $order
     */
    public function getOriginalOrder($orderIncrementId)
    {
        $order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);
        return $order;
    }

     /**
      * Get Address data
      *
      * @param int $addressId
      *
      * @return \Magento\Customer\Api\Data\AddressInterface
      */
    public function getAddressData($addressId)
    {
        try {
            $addressData = $this->addressRepository->getById($addressId);
        } catch (\Exception $e) {
            $this->wplogger->info(__('Address data not found:'). $e->getMessage());
        };
        return $addressData;
    }

    /**
     * Retrieve text of street lines, concatenated using LF symbol
     *
     * @param string $streetData
     * @return string
     */
    public function getStreetFull($streetData)
    {
        $street = $streetData;
        return is_array($street) ? implode("\n", $street) : ($street ?? '');
    }

    /**
     * Get Shipping Method
     *
     * @param string $orderIncrementId
     * @param string $addressId
     * @return string
     */

    public function getShippingMethod($orderIncrementId, $addressId)
    {
        $order = $this->getOriginalOrder($orderIncrementId);
        $storeId = $order->getStoreId();
        $addressData = $this->getAddressData($addressId);
        $quote = $this->cartRepository->get($order->getQuoteId());
        /** @var $request RateRequest */
        $request = $this->rateRequestFactory->create();
        $request->setAllItems($quote->getAllItems());
        $request->setDestCountryId($addressData->getCountryId());
        $request->setDestRegionId($addressData->getRegionId());
        if ($addressData->getRegionId()) {
            $request->setDestRegionCode($addressData->getRegionId());
        }
        $request->setDestStreet($this->getStreetFull($addressData->getStreet()));
        $request->setDestCity($addressData->getCity());
        $request->setDestPostcode($addressData->getPostcode());
        $baseSubtotal = $order->getBaseSubtotal();
        $request->setPackageValue($baseSubtotal);
        $baseSubtotalWithDiscount = $baseSubtotal + $order->getBaseDiscountAmount();
        $packageWithDiscount =  $baseSubtotalWithDiscount;
        $request->setPackageValueWithDiscount($packageWithDiscount);
        $request->setPackageWeight($order->getWeight());
        $request->setPackageQty($order->getTotalQtyOrdered());

        /**
         * Need for shipping methods that use insurance based on price of physical products
         */
        $packagePhysicalValue =$baseSubtotal;
        $request->setPackagePhysicalValue($packagePhysicalValue);

        /**
         * Store and website identifiers specified from StoreManager
         */
        $request->setStoreId($storeId);
        $request->setWebsiteId($this->storeManager->getWebsite()->getId());
        /**
         * $request->setFreeShipping($this->getFreeShipping());
        */
        /**
         * Currencies need to convert in free shipping
         */
        $request->setBaseCurrency($order->getBaseCurrencyCode());
        $request->setPackageCurrency($order->getBaseCurrencyCode());
        /**
         * $request->setLimitCarrier($this->getLimitCarrier());
         */
        $baseSubtotalInclTax = $order->getBaseSubtotalInclTax();
        $request->setBaseSubtotalInclTax($baseSubtotalInclTax);
        $request->setBaseSubtotalWithDiscountInclTax(
            $order->getBaseSubtotalWithDiscount() +
            $order->getBaseTaxAmount()
        );

        $result = $this->rateCollector->create()->collectRates($request)->getResult();

        $shippingRatesArr = [];
        if ($result) {
            $shippingRates = $result->getAllRates();
            
            foreach ($shippingRates as $key => $shippingRate) {
                $shippingRatesArr[$key]['carrier'] = $shippingRate->getData('carrier');
                $shippingRatesArr[$key]['carrier_title'] = $shippingRate->getData('carrier_title');
                $shippingRatesArr[$key]['method'] = $shippingRate->getData('method');
                $shippingRatesArr[$key]['method_title'] = $shippingRate->getData('method_title');
                $shippingRatesArr[$key]['price'] = $shippingRate->getData('price');
                $shippingRatesArr[$key]['cost'] = $shippingRate->getData('cost');
            }
        }

        return json_encode($shippingRatesArr);
    }
}
