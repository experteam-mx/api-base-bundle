<?php

namespace Experteam\ApiBaseBundle;

use Experteam\ApiBaseBundle\DependencyInjection\Compiler\FixturesCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ExperteamApiBaseBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new FixturesCompilerPass());
    }
}