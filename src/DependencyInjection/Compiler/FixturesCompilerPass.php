<?php

namespace Experteam\ApiBaseBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class FixturesCompilerPass
{
    public function process(ContainerBuilder $container) : void
    {
        $taggedServices = $container->findTaggedServiceIds('doctrine.fixture.orm');
    }

}