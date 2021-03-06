<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\ProductBundle\Async\Topics;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Sends MQ message to resize product images.
 */
class ProductImageResizeListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    /** @var MessageProducerInterface */
    private $producer;

    /**
     * @param MessageProducerInterface $producer
     */
    public function __construct(MessageProducerInterface $producer)
    {
        $this->producer = $producer;
    }

    /**
     * @param ProductImageResizeEvent $event
     */
    public function resizeProductImage(ProductImageResizeEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->producer->send(Topics::PRODUCT_IMAGE_RESIZE, $event->getData());
    }
}
