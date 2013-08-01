<?php

namespace ZF\Rpc;

use Zend\Mvc\Controller\AbstractActionController as BaseAbstractActionController;
use Zend\Mvc\MvcEvent;
use Zend\View\Model;

class RpcController extends BaseAbstractActionController
{

    protected $wrappedCallable;

    public function setWrappedCallable($wrappedCallable)
    {
        $this->wrappedCallable = $wrappedCallable;
    }

    public function onDispatch(MvcEvent $e)
    {
        $routeMatch = $e->getRouteMatch();

        /** @var $parameterData \ZF\ContentNegotiation\ParameterDataContainer */
        $contentNegotiationParams = $e->getParam('ZFContentNegotiationParameterData');
        if ($contentNegotiationParams) {
            $routeParameters = $contentNegotiationParams->getRouteParams();
        } else {
            $routeParameters = $routeMatch->getParams();
        }

        $parameterMatcher = new ParameterMatcher($e);

        // match route params to dispatchable parameters
        if ($this->wrappedCallable instanceof \Closure) {
            $callable = $this->wrappedCallable;
        } elseif (is_array($this->wrappedCallable) && is_callable($this->wrappedCallable)) {
            $callable = $this->wrappedCallable;
        } elseif (is_object($this->wrappedCallable) || is_null($this->wrappedCallable)) {
            $action = $routeMatch->getParam('action', 'not-found');
            $method = static::getMethodFromAction($action);
            $callable = (is_null($this->wrappedCallable) && get_class($this) != __CLASS__) ? $this : $this->wrappedCallable;
            if (!method_exists($callable, $method)) {
                $method = 'notFoundAction';
            }
            $callable = array($callable, $method);
        } else {
            throw new \Exception('RPC Controller Not Understood');
        }

        $dispatchParameters = $parameterMatcher->getMatchedParameters($callable, $routeParameters);
        $result = call_user_func_array($callable, $dispatchParameters);
        $result = $this->transformResult($result);
        $e->setResult($result);
    }

    protected function transformResult($result)
    {
        if ($result instanceof Model\ModelInterface) {
            return $result;
        }
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
