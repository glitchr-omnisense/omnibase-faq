<?php

namespace Base\Faq\DependencyInjection;

use Base\Bundle\AbstractBaseExtension;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class FaqExtension extends AbstractBaseExtension
{
    public function getConfiguration(array $config, ContainerBuilder $container): FaqConfiguration
    {
        return new FaqConfiguration();
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        (new PhpFileLoader($container, new FileLocator(\dirname(__DIR__, 2).'/config')))->load('services.php');
        $configuration = new FaqConfiguration();
        $config = (new Processor())->processConfiguration($configuration, $configs);
        $this->setConfiguration($container, $config, $configuration->getTreeBuilder()->buildTree()->getName());
    }
}
