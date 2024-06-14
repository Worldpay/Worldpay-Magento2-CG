<?php
/**
 * @copyright 2024 Sapient
 */
namespace Sapient\Worldpay\Model\Config;

class CronConfig extends \Magento\Framework\App\Config\Value
{
    /**
     * @var string crontab/worldpay_pending_order_cleanup/shedule
     */
    protected const CRON_STRING_PATH =
     'crontab/worldpay_pending_order_cleanup/jobs/pending_order_cleanup/schedule/cron_expr';
    
    /**
     * @var string crontab/worldpay_pending_order_cleanup/job
     */
    protected const CRON_MODEL_PATH =
    'crontab/worldpay_pending_order_cleanup/jobs/sapient_worldpay_cron_job/run/model';

    /**
     * @var Magento\Framework\App\Config\ValueFactory
     */
    protected $_configValueFactory;

    /**
     * @var string $runModelPath
     */
    protected $_runModelPath = '';

    /**
     * Constructor
     *
     * @param Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Config\ValueFactory $configValueFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param \Sapient\Worldpay\Cron $runModelPath
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        $runModelPath = '',
        array $data = []
    ) {
        $this->_runModelPath = $runModelPath;
        $this->_configValueFactory = $configValueFactory;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * After Save Event
     */

    public function afterSave()
    {
        $frequency =  $this->getData(
            'groups/worldpay/order_cleanup_cron/order_cleanup_option/value'
        );
        $cronExprArray = [
            '*',
            '*',
            $frequency == \Magento\Cron\Model\Config\Source\Frequency::CRON_MONTHLY
             ? $frequency : '*',
            '*',
            '*',
        ];

        $cronExprString = join(' ', $cronExprArray);

        try {
            $this->_configValueFactory->create()->load(
                self::CRON_STRING_PATH,
                'path'
            )->setValue(
                $cronExprString
            )->setPath(
                self::CRON_STRING_PATH
            )->save();
            $this->_configValueFactory->create()->load(
                self::CRON_MODEL_PATH,
                'path'
            )->setValue(
                $this->_runModelPath
            )->setPath(
                self::CRON_MODEL_PATH
            )->save();
        } catch (\Exception $e) {
            throw new SpecificException($e->getMessage());
        }

        return parent::afterSave();
    }
}
