<?php

/**
 * @author            ${author.name} (${author.email})
 * @website           ${author.url}
 * @copyright         ${copyrights}
 * @license           ${license.url} ${license.name}
 * @package           ${package}
 * @subpackage        ${subpackage}
 */

\defined('_JEXEC') or die;

use BPExtensions\Component\BPGallery\Administrator\Extension\BPGalleryComponent;
use BPExtensions\Component\BPGallery\Administrator\Helper\AssociationsHelper;
use Joomla\CMS\Association\AssociationExtensionInterface;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\CategoryFactory;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\HTML\Registry;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * The BP Gallery service provider.
 */
return new class () implements ServiceProviderInterface {

    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     */
    public function register(Container $container): void
    {
        $container->set(AssociationExtensionInterface::class, new AssociationsHelper());

        $container->registerServiceProvider(new CategoryFactory('\\BPExtensions\\Component\\BPGallery'));
        $container->registerServiceProvider(new MVCFactory('\\BPExtensions\\Component\\BPGallery'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\BPExtensions\\Component\\BPGallery'));
        $container->registerServiceProvider(new RouterFactory('\\BPExtensions\\Component\\BPGallery'));

        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new BPGalleryComponent($container->get(ComponentDispatcherFactoryInterface::class));

                $component->setRegistry($container->get(Registry::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                $component->setCategoryFactory($container->get(CategoryFactoryInterface::class));
                $component->setAssociationExtension($container->get(AssociationExtensionInterface::class));
                $component->setRouterFactory($container->get(RouterFactoryInterface::class));

                return $component;
            }
        );
    }
};
