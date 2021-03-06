<?php

namespace Oro\Bundle\ProductBundle\Duplicator;

use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * This class helps duplicate product with independent related fields
 */
class ProductDuplicator
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var SkuIncrementorInterface
     */
    protected $skuIncrementor;

    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @var AttachmentProvider
     */
    protected $attachmentProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EventDispatcherInterface $eventDispatcher
     * @param FileManager $fileManager
     * @param AttachmentProvider $attachmentProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EventDispatcherInterface $eventDispatcher,
        FileManager $fileManager,
        AttachmentProvider $attachmentProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->eventDispatcher = $eventDispatcher;
        $this->fileManager = $fileManager;
        $this->attachmentProvider = $attachmentProvider;
    }

    /**
     * @param Product $product
     * @return Product
     * @throws \Exception
     */
    public function duplicate(Product $product)
    {
        $objectManager = $this->doctrineHelper->getEntityManager($product);
        $objectManager->getConnection()->beginTransaction();

        try {
            $productCopy = $this->createProductCopy($product);

            $objectManager->persist($productCopy);
            $objectManager->flush();

            $this->eventDispatcher->dispatch(
                new ProductDuplicateAfterEvent($productCopy, $product),
                ProductDuplicateAfterEvent::NAME
            );

            $objectManager->getConnection()->commit();
        } catch (\Exception $e) {
            $objectManager->getConnection()->rollBack();
            throw $e;
        }

        return $productCopy;
    }

    /**
     * @param SkuIncrementorInterface $skuIncrementor
     */
    public function setSkuIncrementor(SkuIncrementorInterface $skuIncrementor)
    {
        $this->skuIncrementor = $skuIncrementor;
    }

    /**
     * @param Product $product
     * @return Product
     */
    protected function createProductCopy(Product $product)
    {
        $productCopy = clone $product;

        $productCopy->setSku($this->skuIncrementor->increment($product->getSku()));
        $productCopy->setStatus(Product::STATUS_DISABLED);

        $this->cloneChildObjects($product, $productCopy);

        return $productCopy;
    }

    /**
     * @param Product $product
     * @param Product $productCopy
     */
    protected function cloneChildObjects(Product $product, Product $productCopy)
    {
        $this->cloneUnitPrecisions($product, $productCopy);
        $this->cloneFallbackValues($product, $productCopy);
        $this->cloneImages($product, $productCopy);
        $this->cloneAttachments($product, $productCopy);

        $pageTemplate = $product->getPageTemplate();
        if ($pageTemplate) {
            $productCopy->setPageTemplate(clone $pageTemplate);
        }
    }

    /**
     * @param Product $product
     * @param Product $productCopy
     */
    private function cloneUnitPrecisions(Product $product, Product $productCopy)
    {
        $primaryPrecision = $product->getPrimaryUnitPrecision();
        if ($primaryPrecision) {
            $productCopy->setPrimaryUnitPrecision(clone $primaryPrecision);
        }

        foreach ($product->getAdditionalUnitPrecisions() as $unitPrecision) {
            $productCopy->addAdditionalUnitPrecision(clone $unitPrecision);
        }
    }

    /**
     * @param Product $product
     * @param Product $productCopy
     */
    private function cloneFallbackValues(Product $product, Product $productCopy)
    {
        foreach ($product->getNames() as $name) {
            $productCopy->addName(clone $name);
        }

        foreach ($product->getDescriptions() as $description) {
            $productCopy->addDescription(clone $description);
        }

        foreach ($product->getShortDescriptions() as $shortDescription) {
            $productCopy->addShortDescription(clone $shortDescription);
        }
    }

    /**
     * @param Product $product
     * @param Product $productCopy
     */
    private function cloneImages(Product $product, Product $productCopy)
    {
        foreach ($product->getImages() as $productImage) {
            $productImageCopy = clone $productImage;
            $productImageCopy->setProduct($productCopy);

            $imageFileCopy = $this->fileManager->cloneFileEntity($productImageCopy->getImage());
            if (!$imageFileCopy) {
                continue;
            }
            $productImageCopy->setImage($imageFileCopy);

            $this->doctrineHelper->getEntityManager($productImageCopy)->persist($productImageCopy);
        }
    }

    /**
     * @param Product $product
     * @param Product $productCopy
     */
    private function cloneAttachments(Product $product, Product $productCopy)
    {
        $attachments = $this->attachmentProvider->getEntityAttachments($product);
        foreach ($attachments as $attachment) {
            $attachmentCopy = clone $attachment;
            $attachmentFileCopy = $this->fileManager->cloneFileEntity($attachment->getFile());
            if (!$attachmentFileCopy) {
                continue;
            }
            $attachmentCopy->setFile($attachmentFileCopy);

            $attachmentCopy->setTarget($productCopy);

            $this->doctrineHelper->getEntityManager($attachmentCopy)->persist($attachmentCopy);
        }
    }
}
