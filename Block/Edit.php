<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Block;

class Edit extends \Magento\Framework\View\Element\Template
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
     * @var \Sapient\Worldpay\Helper\Data
     */
    protected $worldpayHelper;
     /**
      * @var array
      */
    protected static $_months;
     /**
      * @var array
      */
    protected static $_expiryYears;
    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Sapient\Worldpay\Model\SavedTokenFactory $savecard
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Sapient\Worldpay\Helper\Data $worldpayHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Sapient\Worldpay\Model\SavedTokenFactory $savecard,
        \Magento\Customer\Model\Session $customerSession,
        \Sapient\Worldpay\Helper\Data $worldpayHelper,
        array $data = []
    ) {
        $this->_savecard = $savecard;
        $this->_customerSession = $customerSession;
        $this->worldpayHelper = $worldpayHelper;
        parent::__construct($context, $data);
    }

    /**
     * Retrive savecard Deatil
     *
     * @return object
     */
    public function getTokenData()
    {
        if (!($customerId = $this->_customerSession->getCustomerId())) {
            return false;
        }
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            return $this->_savecard->create()->load($id);
        }
    }

    /**
     * Helps to build year html dropdown
     *
     * @return array
     */
    public function getMonths()
    {
        if (!self::$_months) {
            self::$_months = ['' => $this->getCheckoutLabelbyCode('CO6') ?: __('Month')];
            for ($i = 1; $i < 13; $i++) {
                $month = str_pad($i, 2, '0', STR_PAD_LEFT);
                self::$_months[$month] = date("$i - F", mktime(0, 0, 0, $i, 1));
            }
        }

        return self::$_months;
    }
    /**
     * Account Label b yCode
     *
     * @param string $labelCode
     * @return array
     */

    public function getAccountLabelbyCode($labelCode)
    {
        return $this->worldpayHelper->getAccountLabelbyCode($labelCode);
    }
    /**
     * Helps to build year html dropdown
     *
     * @param string $labelCode
     * @return array
     */

    public function getCheckoutLabelbyCode($labelCode)
    {
        return $this->worldpayHelper->getCheckoutLabelbyCode($labelCode);
    }
    /**
     * Helps to build year html dropdown
     *
     * @return array
     */
    public function getExpiryYears()
    {
        if (!self::$_expiryYears) {
            self::$_expiryYears = ['' => $this->getCheckoutLabelbyCode('CO7') ?: __('Year')];
            $year = date('Y');
            $endYear = ($year + 20);
            while ($year < $endYear) {
                self::$_expiryYears[$year] = $year;
                $year++;
            }
        }
        return self::$_expiryYears;
    }
}
