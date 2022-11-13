<?php
/**
 * Sapient 2022
 */
namespace Sapient\Worldpay\Block;

use Magento\Framework\Serialize\Serializer\Json;

class WalletsPay extends \Magento\Framework\View\Element\Template
{
    /**
     * @var array
     */
    protected $jsLayout;
    /**
     * @var Magento\Framework\Serialize\Serializer\Json
     */
    protected $serializer;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param Json $serializer
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        Json $serializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->jsLayout = isset($data['jsLayout']) && is_array($data['jsLayout']) ? $data['jsLayout'] : [];
        $this->serializer = $serializer;
    }

    /**
     * Get JS layout
     *
     * @return string
     */
    public function getJsLayout()
    {
        return $this->serializer->serialize($this->jsLayout);
    }
}
