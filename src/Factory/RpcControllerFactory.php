<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Rpc\Factory;

use InvalidArgumentException;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
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

        if (! $serviceLocator->has('Config')) {
            return false;
        }

        $config = $serviceLocator->get('Config');
        if (! isset($config['zf-rpc'])
            || ! isset($config['zf-rpc'][$requestedName])
        ) {
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
     * @param ServiceLocatorInterface $controllerManager
     * @param $name
     * @param $requestedName
     * @return mixed|RpcController
     * @throws \Exception
     */
    public function createServiceWithName(ServiceLocatorInterface $controllerManager, $name, $requestedName)
    {
        $serviceLocator = $controllerManager->getServiceLocator();
        $config         = $serviceLocator->get('Config');
        $callable       = $config['zf-rpc'][$requestedName]['callable'];

        if (! is_string($callable) && ! is_callable($callable)) {
            throw new InvalidArgumentException('Unable to create a controller from the configured zf-rpc callable');
        }

        if (is_string($callable)
            && strpos($callable, '::') !== false
        ) {
            $callable = $this->marshalCallable($callable, $controllerManager, $serviceLocator);
        }

        $controller = new RpcController();
        $controller->setWrappedCallable($callable);
        return $controller;
    }

    /**
     * Marshal an instance method callback from a given string.
     *
     * @param mixed $string String of the form class::method
     * @param ServiceLocatorInterface $controllers
     * @param ServiceLocatorInterface $services
     * @return callable
     */
    private function marshalCallable($string, ServiceLocatorInterface $controllers, ServiceLocatorInterface $services)
    {
        list($class, $method) = explode('::', $string, 2);

        if ($controllers->has($class)) {
            return [$controllers->get($class), $method];
        }

        if ($services->has($class)) {
            return [$services->get($class), $method];
        }

        if (! class_exists($class)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot create callback %s as class %s does not exist',
                $string,
                $class
            ));
        }

        return [new $class(), $method];
    }
}
