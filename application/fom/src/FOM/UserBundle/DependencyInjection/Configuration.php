<?php

/**
 * @author Christian Wygoda
 */

namespace FOM\UserBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface {
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder() {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('fom_user');

        $rootNode
            ->children()
                ->scalarNode('auto_create_log_table')
                    ->defaultTrue()
                ->end()
                ->scalarNode('login_check_log_time')
                    ->defaultValue("-5 minutes")
                ->end()
                ->scalarNode('login_attempts_before_delay')
                    ->defaultValue(3)
                ->end()
                ->scalarNode('login_delay_after_fail')
                    ->defaultValue(2)
                ->end()
                ->scalarNode('selfregister')
                    ->defaultFalse()
                ->end()
                ->scalarNode('reset_password')
                    ->defaultTrue()
                ->end()
                ->scalarNode('max_registration_time')
                    ->defaultValue(24)
                ->end()
                ->scalarNode('max_reset_time')
                    ->defaultValue(24)
                ->end()
                ->scalarNode('mail_from_address')
                    ->isRequired()
                ->end()
                ->scalarNode('mail_from_name')
                    ->isRequired()
                ->end()
                ->scalarNode('profile_entity')
                    ->defaultNull()
                ->end()
                ->scalarNode('profile_formtype')
                    ->defaultNull()
                ->end()
                ->scalarNode('profile_template')
                    ->defaultNull()
                ->end()
                ->arrayNode('profile_assets')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('css')
                            ->prototype('scalar')->end()
                            ->defaultValue(array())
                        ->end()
                        ->arrayNode('js')
                            ->prototype('scalar')->end()
                            ->defaultValue(array())
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('self_registration_groups')
                    ->prototype('scalar')->end()
                    ->defaultValue(array())
                ->end()
                ->scalarNode('use_sspi')
                    ->defaultFalse()
                ->end()
                ->scalarNode('trust_sspi')
                    ->defaultFalse()
                ->end()
                ->arrayNode('user_own_permissions')
                    ->prototype('scalar')->end()
                    ->defaultValue(array('VIEW', 'EDIT'))
                ->end()
            ->end();

        return $treeBuilder;
    }
}
