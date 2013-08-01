<?php
return array(
    'zf-rest' => array(
        // Array of Controller service name / callable pairs
        //
        // Example:
        // 'Api\LoginController' => 'Api\Controller\Login::process',
        //
        // This is only necessary if you are not using standard ZF2
        // Zend\Stdlib\DispatchableInterface or Zend\Mvc\Controller
        // implementations.
    ),
    'controllers' => array(
        'abstract_factories' => array(
            'ZF\Rpc\Factory\RpcControllerFactory',
        ),
    ),
);
