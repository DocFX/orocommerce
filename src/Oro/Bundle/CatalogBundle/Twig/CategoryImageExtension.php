<?php

namespace Oro\Bundle\CatalogBundle\Twig;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to get an filtered image or placeholder for a category:
 *   - category_filtered_image
 *   - category_image_placeholder
 */
class CategoryImageExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @const ORO_CATALOG_CATEGORY_IMAGE_EXTENSION */
    public const ORO_CATALOG_CATEGORY_IMAGE_EXTENSION = 'oro_catalog_category_image_extension';
    
    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('category_filtered_image', [$this, 'getCategoryFilteredImage']),
            new TwigFunction('category_image_placeholder', [$this, 'getCategoryImagePlaceholder'])
        ];
    }

    /**
     * @param File|null $file
     * @param string $filter
     * @return string
     */
    public function getCategoryFilteredImage(?File $file, string $filter): string
    {
        if ($file) {
            $attachmentManager = $this->container->get('oro_attachment.manager');

            return $attachmentManager->getFilteredImageUrl($file, $filter);
        }

        return $this->getCategoryImagePlaceholder($filter);
    }

    /**
     * @param string $filter
     * @return string
     */
    public function getCategoryImagePlaceholder(string $filter): string
    {
        $imagePlaceholderProvider = $this->container->get('oro_catalog.provider.category_image_placeholder');

        return $imagePlaceholderProvider->getPath($filter);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::ORO_CATALOG_CATEGORY_IMAGE_EXTENSION;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_attachment.manager' => AttachmentManager::class,
            'oro_catalog.provider.category_image_placeholder' => ImagePlaceholderProviderInterface::class,
        ];
    }
}
