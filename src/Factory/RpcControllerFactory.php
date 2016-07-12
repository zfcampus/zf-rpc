<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Rpc\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\Rpc\RpcController;

class RpcControllerFactory implements AbstractFactoryInterface
{
    /**
     * Determine if we can create a service with name
     *
     * @param ContainerInterface $container
     * @param $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        if (! $container->has('config')) {
            return false;
        }

        $config = $container->get('config');
        if (! isset($config['zf-rpc'][$requestedName])) {
            return false;
        }

        $config = $config['zf-rpc'][$requestedName];

        if (! is_array($config)
            || ! isset($config['callable'])
        ) {
            return false;
        }

        return true;
    }

    /**
     * Determine if we can create a service with name (v2).
     *
     * Provided for backwards compatibility; proxies to canCreate().
     *
     * @param ServiceLocatorInterface $controllerManager
     * @param $name
     * @param $requestedName
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $controllerManager, $name, $requestedName)
    {
        $container = $controllerManager->getServiceLocator() ?: $controllerManager;
        return $this->canCreate($container, $requestedName);
    }

    /**
     * Create and return an RpcController instance.
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     * @return RpcController
     * @throws ServiceNotCreatedException if the callable configuration value
     *     associated with the controller is not callable.
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config   = $container->get('config');
        $callable = $config['zf-rpc'][$requestedName]['callable'];

        if (! is_string($callable) && ! is_callable($callable)) {
            throw new ServiceNotCreatedException('Unable to create a controller from the configured zf-rpc callable');
        }

        if (is_string($callable)
            && strpos($callable, '::') !== false
        ) {
            $callable = $this->marshalCallable($callable, $container);
        }

        $controller = new RpcController();
        $controller->setWrappedCallable($callable);
        return $controller;
    }

    /**
     * Create and return an RpcController instance (v2).
     *
     * Provided for backwards compatibility; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $controllerManager
     * @param $name
     * @param $requestedName
     * @return RpcController
     * @throws \Exception
     */
    public function createServiceWithName(ServiceLocatorInterface $controllerManager, $name, $requestedName)
    {
        $container = $controllerManager->getServiceLocator() ?: $controllerManager;
        return $this($container, $requestedName);
    }

    /**
     * Marshal an instance method callback from a given string.
     *
     * @param mixed $string String of the form class::method
     * @param ContainerInterface $container
     * @return callable
     */
    private function marshalCallable($string, ContainerInterface $container)
    {
        $callable = false;
        list($class, $method) = explode('::', $string, 2);

        if ($container->has('ControllerManager')) {
            $callable = $this->marshalCallableFromContainer($class, $method, $container->get('ControllerManager'));
        }

        if (! $callable) {
            $callable = $this->marshalCallableFromContainer($class, $method, $container);
        }

        if ($callable) {
            return $callable;
        }

        if (! class_exists($class)) {
            throw new ServiceNotCreatedException(sprintf(
                'Cannot create callback %s as class %s does not exist',
                $string,
                $class
            ));
        }

        return [new $class(), $method];
    }

    /**
     * Attempt to marshal a callable from a container.
     *
     * @param string $class
     * @param string $method
     * @param ContainerInterface $container
     * @return false|callable
     */
    private function marshalCallableFromContainer($class, $method, ContainerInterface $container)
    {
        if (! $container->has($class)) {
            return false;
        }

        return [$container->get($class), $method];
    }
}
