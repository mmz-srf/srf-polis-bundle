<?php

declare(strict_types=1);

namespace SRF\PolisBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @psalm-suppress PartialBadTypeFromSignatureOnStrictFileIssue
 */
class SRFPolisBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->parameters()
            ->set('srf_polis.api.clientId', $config['api']['clientId'])
            ->set('srf_polis.api.clientSecret', $config['api']['clientSecret']);

        $container->import('../config/services.yaml');
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->arrayNode('api')
                ->children()
                    ->scalarNode('clientId')->end()
                    ->scalarNode('clientSecret')->end()
                ->end()
            ->end()
        ->end();
    }
}
