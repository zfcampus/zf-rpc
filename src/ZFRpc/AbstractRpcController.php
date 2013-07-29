<?php

namespace ZFRpc\Controller;

use Zend\Mvc\Controller\AbstractActionController as BaseAbstractActionController;
use Zend\Mvc\MvcEvent;
use Zend\View\Model;

abstract class AbstractRpcController extends BaseAbstractActionController
{

    public function onDispatch(MvcEvent $e)
    {
        $routeMatch = $e->getRouteMatch();

        $action = $routeMatch->getParam('action', 'not-found');
        $method = static::getMethodFromAction($action);

        if (!method_exists($this, $method)) {
            $method = 'notFoundAction';
        }

        /** @var $parameterData \ZFContentNegotiation\ParameterDataContainer */
        $parameterData = $this->getEvent()->getParam('ZFContentNegotiationParameterData');
        if ($parameterData) {
            $parameters = $parameterData->getRouteParams();
        } else {
            $parameters = $routeMatch->getParams();
            unset($parameters['_gateway']);
        }

        // match parameter
        $parameterMatcher = new ParameterMatcher($e);
        $dispatchParameters = $parameterMatcher->getMatchedParameters(array($this, $method), $parameters);

        // call action
        $result = call_user_func_array(array($this, $method), $dispatchParameters);

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

        return $result;
    }

}
