<?php

namespace ZF\Rpc;

use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;

class OptionsListener extends AbstractListenerAggregate
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @param  array $config 
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param  EventManagerInterface $events 
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach('route', array($this, 'onRoute'), -100);
    }

    /**
     * @param  MvcEvent $event 
     * @return void|\Zend\Http\Response
     */
    public function onRoute(MvcEvent $event)
    {
        $matches = $event->getRouteMatch();
        if (!$matches) {
            // No matches, nothing to do
            return;
        }

        $controller = $matches->getParam('controller', false);
        if (!$controller) {
            // No controller in the matches, nothing to do
            return;
        }

        if (!array_key_exists($controller, $this->config)) {
            // No matching controller in our configuration, nothing to do
            return;
        }

        $config = $this->config[$controller];

        if (!array_key_exists('http_options', $config)
            || empty($config['http_options'])
        ) {
            // No HTTP options set for controller, nothing to do
            return;
        }

        $request = $event->getRequest();
        if (!$request instanceof Request) {
            // Not an HTTP request? nothing to do
            return;
        }

        $options = $this->normalizeOptions($config['http_options']);

        $method = $request->getMethod();
        if ($method === Request::METHOD_OPTIONS) {
            // OPTIONS request? return response with Allow header
            return $this->getOptionsResponse($event, $options);
        }

        if (in_array($method, $options)) {
            // Valid HTTP method; nothing to do
            return;
        }

        // Invalid method; return 405 response
        return $this->get405Response($event, $options);
    }

    /**
     * Normalize an options array
     *
     * If a string is provided, create an array with that string.
     *
     * Ensure all options in the array are UPPERCASE.
     * 
     * @param  string|array $options 
     * @return array
     */
    protected function normalizeOptions($options)
    {
        if (is_string($options)) {
            $options = (array) $options;
        }

        array_walk($options, 'strtoupper');
        return $options;
    }

    /**
     * Create the Allow header
     * 
     * @param  array $options 
     * @param  Response $response 
     */
    protected function createAllowHeader(array $options, Response $response)
    {
        $headers = $response->getHeaders();
        $headers->addHeaderLine('Allow', implode(',', $options));
    }

    /**
     * Prepare and return an OPTIONS response
     *
     * Creates an empty response with an Allow header.
     * 
     * @param  MvcEvent $event 
     * @param  array $options 
     * @return Response
     */
    protected function getOptionsResponse(MvcEvent $event, array $options)
    {
        $response = $event->getResponse();
        $this->createAllowHeader($options, $response);
        return $response;
    }

    /**
     * Prepare a 405 response
     * 
     * @param  MvcEvent $event 
     * @param  array $options 
     * @return Response
     */
    protected function get405Response(MvcEvent $event, array $options)
    {
        $response = $this->getOptionsResponse($event, $options);
        $response->setStatusCode(405, 'Method Not Allowed');
        return $response;
    }
}
