<?php

namespace ZF\Rpc\Factory;

use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\DispatchableInterface;
use ZF\Rpc\RpcController;

class RpcControllerFactory implements AbstractFactoryInterface
{
    /**
     * Determine if we can create a service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $controllerManager, $name, $requestedName)
    {
        $serviceLocator = $controllerManager->getServiceLocator();

        if (!$serviceLocator->has('Config')) {
            return false;
        }

        $config = $serviceLocator->get('Config');
        if (!isset($config['zf-rpc']) || !is_array($config['zf-rpc'])) {
            return false;
        }

        $config = $config['zf-rpc'];

        // must have some kind of callable
        if (!isset($config[$requestedName])) {
            return false;
        }

        return true;
    }

    /**
     * Create service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return mixed
     */
    public function createServiceWithName(ServiceLocatorInterface $controllerManager, $name, $requestedName)
    {
        $serviceLocator = $controllerManager->getServiceLocator();

        $config = $serviceLocator->get('Config');
        $config = $config['zf-rpc'];

        $callable = $config[$requestedName];

        if (is_string($callable)) {
            $controller = new RpcController();
            if (strpos($callable, '::') !== false) {
                $wrappedCallable = explode('::', $callable, 2);
                $wrappedCallable[0] = new $wrappedCallable[0];
            } else {
                $wrappedCallable = $callable;
            }
            $controller->setWrappedCallable($wrappedCallable);
        } elseif (is_callable($callable)) {
            $controller = new RpcController();
            $controller->setWrappedCallable($callable);
        } else {
            throw new \Exception('Unable to create a controller from the configured zf-rpc callable');
        }

        return $controller;
    }
}
