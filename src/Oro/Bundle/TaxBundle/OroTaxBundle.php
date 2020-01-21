<?php

namespace Oro\Bundle\TaxBundle;

use Oro\Bundle\TaxBundle\DependencyInjection\CompilerPass\ResolverEventConnectorPass;
use Oro\Bundle\TaxBundle\DependencyInjection\OroTaxExtension;
use Oro\Component\DependencyInjection\Compiler\PriorityNamedTaggedServiceCompilerPass;
use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The TaxBundle bundle class.
 */
class OroTaxBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new PriorityNamedTaggedServiceCompilerPass(
            'oro_tax.provider.tax_provider_registry',
            'oro_tax.tax_provider',
            'alias'
        ));
        $container->addCompilerPass(new PriorityNamedTaggedServiceCompilerPass(
            'oro_tax.address_matcher_registry',
            'oro_tax.address_matcher',
            'type'
        ));
        $container->addCompilerPass(new PriorityTaggedLocatorCompilerPass(
            'oro_tax.factory.tax',
            'oro_tax.tax_mapper',
            'class'
        ));
        $container->addCompilerPass(new ResolverEventConnectorPass());
    }

    /** {@inheritdoc} */
    public function getContainerExtension()
    {
        return new OroTaxExtension();
    }
}
