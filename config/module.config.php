<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Rpc;

return [
    'zf-rpc' => [
        // Array of Controller service name / configuration
        //
        // Configuration should include:
        // - http_methods: allowed HTTP methods
        // - route_name: name of route that will match this endpoint
        //
        // Configuration may include:
        // - callable: the PHP callable to invoke; only necessary if not
        //   using a standard ZF2 Zend\Stdlib\DispatchableInterface or
        //   Zend\Mvc\Controller implementation.
        //
        // Example:
        //
        //   'Api\LoginController' => [
        //       'http_methods' => ['POST'],
        //       'route_name'   => 'api-login',
        //       'callable'     => 'Api\Controller\Login::process',
        //   ],
    ],
    'controllers' => [
        'abstract_factories' => [
            Factory\RpcControllerFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            OptionsListener::class => Factory\OptionsListenerFactory::class,
        ],
    ],
];
