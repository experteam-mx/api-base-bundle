<?php

namespace Experteam\ApiBaseBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class ExperteamApiBaseExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $config = (new Processor())->processConfiguration(new Configuration(), $configs);
        $container->setParameter('experteam_api_base.params', $config['params']);
        $container->setParameter('experteam_api_base.elk_logger', $config['elk_logger']);
        $container->setParameter('experteam_api_base.fixtures', $config['fixtures']);
        $container->setParameter('experteam_api_base.timezone', $config['timezone']);
        $container->setParameter('experteam_api_base.appkey', $config['appkey']);
        $container->setParameter('experteam_api_base.etag', $config['etag']);
        $container->setParameter('experteam_api_base.auth', $config['auth']);
        $container->setParameter('experteam_api_base.delay_alert', $config['delay_alert']);
    }

}