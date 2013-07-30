<?php

namespace ZF\Rpc;

use Zend\Mvc\MvcEvent;
use Zend\View\Model;

class InstanceMethodController extends AbstractRpcController
{
    protected $instance;

    public function __construct($instance)
    {
        $this->instance = $instance;
    }

    /**
     * Execute the request
     *
     * @param  MvcEvent $e
     * @return mixed
     */
    public function onDispatch(MvcEvent $e)
    {
        $routeMatch = $e->getRouteMatch();

        $action = $routeMatch->getParam('action', 'not-found');
        $method = static::getMethodFromAction($action);

        if (!method_exists($this->instance, $method)) {
            $method = 'notFoundAction';
        }

        /** @var $parameterData \ZF\ContentNegotiation\ParameterDataContainer */
        $parameterData = $this->getEvent()->getParam('ZFContentNegotiationParameterData');
        if ($parameterData) {
            $parameters = $parameterData->getRouteParams();
        } else {
            $parameters = $routeMatch->getParams();
            unset($parameters['_gateway']);
        }

        // match parameter
        $parameterMatcher = new ParameterMatcher($e);
        $dispatchParameters = $parameterMatcher->getMatchedParameters(array($this->instance, $method), $parameters);

        // call action
        $result = call_user_func_array(array($this->instance, $method), $dispatchParameters);

        if (!$result instanceof Model\ModelInterface) {
            if ($result instanceof \JsonSerializable) {
                $result = $result->jsonSerialize();
            }
            if (is_object($result)) {
                if (method_exists($result, 'toArray')) {
                    $result = $result->toArray();
                } else {
                    throw new \Exception('Responses must be an array or an object that implements JsonSerializable or has a method called toArray()');
                }
            }
            if ($result === null) {
                $result = array();
            }
            $result = new Model\JsonModel($result);
            $result->setTerminal(true);
        }

        $e->setResult($result);
        return $result;
    }

    /**
     * Transform an "action" token into a method name
     *
     * @param  string $action
     * @return string
     */
    public static function getMethodFromAction($action)
    {
        $method  = str_replace(array('.', '-', '_'), ' ', $action);
        $method  = ucwords($method);
        $method  = str_replace(' ', '', $method);
        $method  = lcfirst($method);

        return $method;
    }

}
