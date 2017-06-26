<?php
/**
 * @LICENSE_TEXT
 */

namespace EventBand\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * Class RouterConfiguration
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class RouterConfiguration implements SectionConfiguration
{
    private static $DEFAULT_ROUTER = [
        'type' => 'pattern',
        'parameters' => '{name}'
    ];

    /**
     * {@inheritDoc}
     */
    public function getSectionDefinition()
    {
        $builder = new TreeBuilder();
        $root = $builder->root('routers');
        $root->info('Pattern "{name}" router "default" is added');

        $root
            ->useAttributeAsKey('name')
            ->defaultValue(['default' => self::$DEFAULT_ROUTER])
            ->prototype('array')
                ->children()
                    ->scalarNode('pattern')->defaultValue(self::$DEFAULT_ROUTER['parameters'])->end()
                ->end()
                ->validate()
                    ->always()
                    ->then(function ($router) {
                        if (count($router) !== 1) {
                            throw new \InvalidArgumentException(sprintf(
                                'Expected only 1 adapter. Got %d: %s',
                                count($router), implode(', ', array_keys($router))
                            ));
                        }

                        return ['type' => key($router), 'parameters' => current($router)];
                    })
                ->end()
            ->end()
            ->validate()
                ->always()
                ->then(function ($routers) {
                    if (!isset($routers['default'])) {
                        $routers['default'] = self::$DEFAULT_ROUTER;
                    }

                    return $routers;
                })
            ->end()
        ;

        return $root;
    }
}