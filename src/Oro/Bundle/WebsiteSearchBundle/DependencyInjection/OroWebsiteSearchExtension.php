<?php

namespace Oro\Bundle\WebsiteSearchBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class OroWebsiteSearchExtension extends Extension
{
    const ALIAS = 'oro_website_search';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->prependExtensionConfig($this->getAlias(), $config);
    }

    /** {@inheritdoc} */
    public function getAlias()
    {
        return self::ALIAS;
    }
}
