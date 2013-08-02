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
        /** @var \Zend\Mvc\Controller\ControllerManager $controllerManager */
        $serviceLocator = $controllerManager->getServiceLocator();

        if (!$serviceLocator->has('Config')) {
            return false;
        }

        $config = $serviceLocator->get('Config');
	if (!isset($config['zf-rpc']) || !isset($config['zf-rpc'][$requestedName])) {
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
        /** @var \Zend\Mvc\Controller\ControllerManager $controllerManager */
        $serviceLocator = $controllerManager->getServiceLocator();

        $config = $serviceLocator->get('Config');
	$callable = $config['zf-rpc'][$requestedName];

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
