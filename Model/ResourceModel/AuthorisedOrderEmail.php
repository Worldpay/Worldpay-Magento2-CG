<?php

namespace Sapient\Worldpay\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class AuthorisedOrderEmail extends AbstractDb
{
    const MAX_ATTEMPT_COUNT = 4;

    protected function _construct(): void
    {
        $this->_init('worldpay_authorised_order_email', 'entity_id');
    }

    public function getPendingEmails($limit = 100, $fromEntityId = 0): array
    {
        $table = $this->getMainTable();
        $sql = $this->getConnection()->select()
            ->from($table)
            ->where("attempt_count < ?", self::MAX_ATTEMPT_COUNT)
            ->where("entity_id > ?", $fromEntityId)
            ->limit($limit);
        return $this->getConnection()->fetchAll($sql);
    }

    public function deleteByIds(array $ids): void
    {
        $connection = $this->getConnection();
        $table = $this->getMainTable();

        $connection->delete($table, ['entity_id IN (?)' => $ids]);
    }

    public function incrementSendAttemptCount(string $entity_id): void
    {
        $connection = $this->getConnection();
        $table = $this->getMainTable();

        $connection->update(
            $table,
            ['attempt_count' => new \Zend_Db_Expr('attempt_count + 1')],
            ['entity_id = ?' => $entity_id]
        );
    }
}
