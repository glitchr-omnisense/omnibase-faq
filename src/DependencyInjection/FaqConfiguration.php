<?php

namespace Base\Faq\DependencyInjection;

use Base\Bundle\AbstractBaseConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class FaqConfiguration extends AbstractBaseConfiguration
{
    private bool $childrenDeclared = false;

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = $this->getTreeBuilder();
        if ($this->childrenDeclared) {
            return $treeBuilder;
        }
        $this->childrenDeclared = true;

        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('ask_enabled')->defaultTrue()->info('The form to ask a question that is missing.')->end()
                ->integerNode('per_section')->min(1)->defaultValue(20)->info('Questions shown per section.')->end()
                ->integerNode('min_delay')->min(0)->defaultValue(4)->info('Seconds between the form being opened and sent: faster is a robot.')->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
