<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Rpc\Factory;

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

        if (!$serviceLocator->has('Config')) {
            return false;
        }

        $config = $serviceLocator->get('Config');
        if (!isset($config['zf-rpc'])
            || !isset($config['zf-rpc'][$requestedName])
        ) {
            return false;
        }

        $config = $config['zf-rpc'][$requestedName];

        if (!is_array($config)
            || !isset($config['callable'])
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

        if (!is_string($callable) && !is_callable($callable)) {
            throw new \Exception('Unable to create a controller from the configured zf-rpc callable');
        }

        if (is_string($callable)
            && strpos($callable, '::') !== false
        ) {
            $wrappedCallable    = explode('::', $callable, 2);
            $wrappedCallable[0] = new $wrappedCallable[0];
            $callable           = $wrappedCallable;
        }

        $controller = new RpcController();
        $controller->setWrappedCallable($callable);
        return $controller;
    }
}
