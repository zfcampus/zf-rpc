ZF RPC
======

[![Build Status](https://travis-ci.org/zfcampus/zf-rpc.png)](https://travis-ci.org/zfcampus/zf-rpc)

Introduction
------------

Module for implementing RPC web services in ZF2.

Enables:

- defining controllers as PHP callables
- creating a whitelist of HTTP request methods; requests outside the whitelist
  will return a 405 "Method Not Allwowed" response with an Allow header
  indicating allowed methods.


Installation
------------

Run the following `composer` command:

```console
$ composer require "zfcampus/zf-rpc:~1.0-dev"
```

Alternately, manually add the following to your `composer.json`, in the `require` section:

```javascript
"require": {
    "zfcampus/zf-rpc": "~1.0-dev"
}
```

And then run `composer update` to ensure the module is installed.

Finally, add the module name to your project's `config/application.config.php` under the `modules`
key:

```php
return array(
    /* ... */
    'modules' => array(
        /* ... */
        'ZF\Rpc',
    ),
    /* ... */
);
```


Configuration
=============

### User Configuration

```php
'zf-rpc' => array(
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
    //   'Api\LoginController' => array(
    //       'http_methods' => array('POST'),
    //       'route_name'   => 'api-login',
    //       'callable'     => 'Api\Controller\Login::process',
    //   ),
),
```

### System Configuration

```php
'controllers' => array(
    'abstract_factories' => array(
        'ZF\Rpc\Factory\RpcControllerFactory',
    ),
),
```

ZF2 Events
==========

### Listeners

#### `ZF\Rpc\OptionsListener`

ZF2 Services
============

### Models

#### `ZF\Rpc\ParameterMatcher`

### Controller

#### `ZF\Rpc\RpcController`
