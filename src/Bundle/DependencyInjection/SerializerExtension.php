<?php declare(strict_types=1);

namespace Kcs\Serializer\Bundle\DependencyInjection;

use Kcs\Serializer\Handler\SubscribingHandlerInterface;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Parameter;

final class SerializerExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        if (\method_exists($container, 'registerForAutoconfiguration')) {
            $container->registerForAutoconfiguration(SubscribingHandlerInterface::class)
                ->addTag('kcs_serializer.handler')
            ;
        }

        if (! $container->getParameter('kernel.debug') && \class_exists(AbstractAdapter::class)) {
            $container->register('kcs_serializer.metadata.cache', AdapterInterface::class)
                ->setFactory(AbstractAdapter::class.'::createSystemCache')
                ->addArgument('')
                ->addArgument(0)
                ->addArgument(new Parameter('container.build_id'))
                ->addArgument('%kernel.cache_dir%/kcs_serializer')
            ;
        }
    }
}
