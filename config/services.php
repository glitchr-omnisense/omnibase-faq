<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return function (ContainerConfigurator $configurator) {
    $src = dirname(__DIR__).'/src';

    $services = $configurator->services();
    $services->defaults()->autowire(true)->autoconfigure(true)->public(false);

    $services->load('Base\\Faq\\', $src.'/')
        ->exclude([$src.'/DependencyInjection/', $src.'/Entity/', $src.'/Controller/Admin/', $src.'/Admin/', $src.'/FaqBundle.php']);

    $services->load('Base\\Faq\\Controller\\Client\\', $src.'/Controller/Client/')
        ->tag('controller.service_arguments');

    if (class_exists('Base\\Admin\\Controller\\AbstractCrudController')) {
        $services->load('Base\\Faq\\Controller\\Admin\\', $src.'/Controller/Admin/')->tag('controller.service_arguments');
        $services->load('Base\\Faq\\Admin\\', $src.'/Admin/');
    }
};
