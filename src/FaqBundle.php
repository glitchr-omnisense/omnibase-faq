<?php

namespace Base\Faq;

use Base\Bundle\AbstractBaseBundle;
use Base\Traits\SingletonTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/** A FAQ on top of omnibase's Thread (the questions) and Taxon (the sections). */
class FaqBundle extends AbstractBaseBundle
{
    use SingletonTrait;

    public function __construct()
    {
        parent::__construct();
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $this->setMapping($this->getPath().'/src/Entity', 'Base\Faq\Entity', 'App\Entity\Faq');
        $this->setMapping($this->getPath().'/src/Repository', 'Base\Faq\Repository', 'App\Repository\Faq');
    }
}
