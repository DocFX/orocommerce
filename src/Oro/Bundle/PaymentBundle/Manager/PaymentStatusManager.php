<?php

namespace Oro\Bundle\PaymentBundle\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

/**
 * Contains methods for managing PaymentStatus entity.
 */
class PaymentStatusManager
{
    /** @var PaymentStatusProviderInterface */
    protected $statusProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var PaymentTransactionProvider */
    protected $paymentTransactionProvider;

    /**
     * @param PaymentStatusProviderInterface $provider
     * @param DoctrineHelper $doctrineHelper
     * @param PaymentTransactionProvider $transactionProvider
     */
    public function __construct(
        PaymentStatusProviderInterface $provider,
        DoctrineHelper $doctrineHelper,
        PaymentTransactionProvider $transactionProvider
    ) {
        $this->statusProvider = $provider;
        $this->doctrineHelper = $doctrineHelper;
        $this->paymentTransactionProvider = $transactionProvider;
    }

    /**
     * @param PaymentTransaction $transaction
     */
    public function updateStatus(PaymentTransaction $transaction)
    {
        $entityClass = $transaction->getEntityClass();
        $entityId = $transaction->getEntityIdentifier();

        $paymentStatusEntity = $this->findPaymentStatus($entityClass, $entityId);
        if (!$paymentStatusEntity) {
            $paymentStatusEntity = $this->createPaymentStatus($entityClass, $entityId);
        }

        $entity = $this->doctrineHelper->getEntityReference($entityClass, $entityId);
        $status = $this->statusProvider->getPaymentStatus($entity);
        $paymentStatusEntity->setPaymentStatus($status);

        $em = $this->doctrineHelper->getEntityManagerForClass(PaymentStatus::class);
        $em->persist($paymentStatusEntity);
        $em->flush($paymentStatusEntity);
    }

    /**
     * @param string $entityClass
     * @param int $entityId
     * @return PaymentStatus
     */
    public function getPaymentStatusForEntity(string $entityClass, int $entityId): PaymentStatus
    {
        $paymentStatusEntity = $this->findPaymentStatus($entityClass, $entityId);
        if (!$paymentStatusEntity) {
            $entity = $this->doctrineHelper->getEntityReference($entityClass, $entityId);
            $status = $this->statusProvider->getPaymentStatus($entity);

            $paymentStatusEntity = $this->createPaymentStatus($entityClass, $entityId);
            $paymentStatusEntity->setPaymentStatus($status);
        }

        return $paymentStatusEntity;
    }

    /**
     * @param string $entityClass
     * @param int $entityId
     * @return PaymentStatus
     */
    private function createPaymentStatus(string $entityClass, int $entityId): PaymentStatus
    {
        $paymentStatusEntity = new PaymentStatus();
        $paymentStatusEntity->setEntityClass($entityClass);
        $paymentStatusEntity->setEntityIdentifier($entityId);

        return $paymentStatusEntity;
    }

    /**
     * @param string $entityClass
     * @param int $entityId
     * @return PaymentStatus|null
     */
    private function findPaymentStatus(string $entityClass, int $entityId): ?PaymentStatus
    {
        /** @var PaymentStatus $paymentStatusEntity */
        $paymentStatusEntity = $this->doctrineHelper
            ->getEntityRepository(PaymentStatus::class)
            ->findOneBy(
                [
                    'entityClass' => $entityClass,
                    'entityIdentifier' => $entityId,
                ]
            );

        return $paymentStatusEntity;
    }
}
