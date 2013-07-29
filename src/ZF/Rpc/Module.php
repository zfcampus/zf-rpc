<?php

namespace ZF\Rpc;

use Zend\Mvc\MvcEvent;
use Zend\Mvc\ModuleRouteListener;

class Module
{
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }

    /**
     * Setup the service configuration
     *
     * @see \Zend\ModuleManager\Feature\ServiceProviderInterface::getServiceConfig()
     */
    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'ZF\Rpc' => function ($sm) {
                    return new Rpc($sm->get('Application'));
                }
            )
        );
    }

    /**
     * Bootstrap time
     *
     * @param MvcEvent $e
     */
    public function onBootstrap($e)
    {
        $app = $e->getApplication();
        $em = $app->getEventManager();

        // setup pre-route configuration
        $em->attach(MvcEvent::EVENT_ROUTE, new ConfigurationListener(), 100);

        // setup Module Route Listeners
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }
}
