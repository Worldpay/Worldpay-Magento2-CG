<?php

namespace Sapient\Worldpay\Helper;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Quote\Model\Quote;
use Sapient\Worldpay\Model\Authorisation\MageOrder;

class ProductOnDemand extends AbstractHelper
{
    /** @var \Sapient\Worldpay\Model\ResourceModel\ProductOnDemand\Order */
    private $productOnDemandOrderResource;

    /** @var \Magento\Catalog\Model\ResourceModel\Product */
    private $productResource;

    private \Sapient\Worldpay\Model\ProductOnDemand\OrderFactory $productOnDemandOrderFactory;
    private \Magento\Quote\Api\CartRepositoryInterface $quoteRepository;
    private \Magento\Checkout\Model\Session $_checkoutSession;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        \Sapient\Worldpay\Model\ProductOnDemand\OrderFactory $productOnDemandOrderFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Sapient\Worldpay\Model\ResourceModel\ProductOnDemand\Order $productOnDemandOrderResource
    ) {
        $this->productOnDemandOrderResource = $productOnDemandOrderResource;
        $this->productResource = $productResource;
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->productOnDemandOrderFactory = $productOnDemandOrderFactory;
        $this->quoteRepository = $quoteRepository;
        $this->_checkoutSession = $checkoutSession;
    }

    public function isProductOnDemandGeneralConfigActive(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            'worldpay/product_on_demand/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function isProductOnDemandQuoteId($quoteId): bool
    {
        if(!$this->isProductOnDemandGeneralConfigActive()) {
            return false;
        }

        $quote = $this->quoteRepository->get($quoteId);
        if ($this->quoteContainsProductOnDemand($quote)) {
            return true;
        }

        return false;
    }

    public function quoteContainsProductOnDemand(Quote $quote): bool
    {
        if(!$this->isProductOnDemandGeneralConfigActive()) {
            return false;
        }

        foreach ($quote->getAllItems() as $item) {
            if (($product = $item->getProduct())
                && $this->isProductOnDemand($product)
            ) {
                return true;
            }
        }
        return false;
    }

    public function isProductOnDemand(Product $product): bool
    {
        $this->productResource->load($product, $product->getId());
        return (
            $product->getProductOnDemand()
            || $product->getData('product_on_demand')
        )
            ?? false;
    }

    public function isProductOnDemandQuote(): bool
    {
        if ($this->quoteContainsProductOnDemand($this->_checkoutSession->getQuote())) {
            return true;
        }
        return false;
    }


    public function _createWorldpayPayOnDemand($orderId, $orderCode, $tokenId, $isZeroAuthOrder = true)
    {
        $model = $this->productOnDemandOrderFactory->create();
        $model->setOrderId($orderId);
        $model->setWorldpayOrderId($orderCode);
        $model->setWorldpayTokenId($tokenId);
        $model->setIsZeroAuthOrder($isZeroAuthOrder);

        $this->productOnDemandOrderResource->save($model);
    }

    public function getPdpLabel()
    {
        return $this->scopeConfig->getValue(
            'worldpay/product_on_demand/pdp_label',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMiniCartLabel()
    {
        return $this->scopeConfig->getValue(
            'worldpay/product_on_demand/minicart_label',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getCartLabel()
    {
        return $this->scopeConfig->getValue(
            'worldpay/product_on_demand/cart_label',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMultiShippingDisabledLabel()
    {
        return $this->scopeConfig->getValue(
            'worldpay/product_on_demand/multi_shipping_disabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
