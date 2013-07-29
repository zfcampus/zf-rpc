<?php
namespace ZFRpc;

use Zend\Mvc\Application;
use Zend\Mvc\Router;
use Zend\Config\Reader\Xml as XmlConfig;
use Zend\Stdlib\ArrayUtils;

class ZFRpc
{
    /**
     * @var Application
     */
    protected $application;

    /**
     * @var Router\Http\TreeRouteStack
     */
    protected $router;

    /**
     * @var \Zend\Mvc\Controller\ControllerManager
     */
    protected $controllerManager;

    /**
     * @var Controller\ControllerAbstractFactory
     */
    protected $controllerAbstractFactory;

    protected $config = array();

    public function __construct(Application $application)
    {
        $this->application = $application;
        $sm = $this->application->getServiceManager();
        /* @var $router \Zend\Mvc\Router\Http\TreeRouteStack */
        $this->router = $sm->get('Router');

        $this->controllerAbstractFactory = new Controller\ControllerAbstractFactory();

        $this->controllerManager = $sm->get('ControllerLoader');
        $this->controllerManager->addAbstractFactory($this->controllerAbstractFactory);
    }

    public function configure(array $apiFirstConfig)
    {
        $this->config = $apiFirstConfig;

        foreach ($this->config as $key => $value) {
            switch (strtolower($key)) {
                case 'route':
                    if (!isset($value[0])) {
                        $value = array($value);
                    }
                    foreach ($value as $route) {
                        $dispatchable = (isset($route['dispatchable'])) ? $route['dispatchable'] : null;
                        $url = (isset($route['url'])) ? $route['url'] : null;
                        $methods = (isset($route['methods'])) ? $route['methods'] : 'GET';

                        // add as route
                        $this->route($url, $dispatchable, $methods /*, $validate, $response, $authenticate*/);
                    }
                    break;
                case 'response':
                    $this->config['response'] = $this->normalizeResponseOptions($value);
                    break;
            }
        }

        return $this;
    }

    public function getConfiguration()
    {
        return $this->config;
    }

    public function route($url, $dispatchable, $methods = 'GET', $validation = null, $response = null, $authenticate = null)
    {
        static $routeCount = 1;

        $routeName = 'zf-rpc-route-' . $routeCount;

        $parameters = array(
            '_rpc' => array(
                'validation' => $validation,
                'response' => $response,
                'gateway' => 'route',
                'authenticate' => $authenticate
            )
        );

        if (is_string($dispatchable) && strpos($dispatchable, '::') !== false) {
            list($controller, $action) = explode('::', $dispatchable, 2);
            if (substr($action, -6) == 'Action') {
                $action = substr($action, 0, -6);
            }
            $parameters['controller'] = $controller;
            $parameters['action'] = $action;
            $this->controllerAbstractFactory->addController($controller);
        } elseif (is_callable($dispatchable)) {
            $parameters['controller'] = 'controller-' . $routeCount;
            $controller = new Controller\CallableController($dispatchable);
            $this->controllerManager->setService('controller-' . $routeCount, $controller);
        }

        $route = new Router\Http\Part(
            new Router\Http\Method($methods),
            false,
            $this->router->getRoutePluginManager(),
            array(new Router\Http\Segment($url, array(), $parameters))
        );

        $this->router->addRoute(
            $routeName,
            $route
        );
        $routeCount++;
        return $route;
    }

    protected function normalizeResponseOptions(array $response)
    {
        if (!ArrayUtils::hasIntegerKeys($response)) {
            $response = array($response);
        }

        foreach ($response as $responseIndex => $responseOptions) {
            foreach ($responseOptions as $optName => $optValue) {
                if ($optName == 'header') {
                    if (!ArrayUtils::hasIntegerKeys($response[$responseIndex][$optName])) {
                        $response[$responseIndex][$optName] = array($optValue);
                    }
                }
            }
        }

        return $response;
    }
}
