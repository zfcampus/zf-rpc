<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Rpc;

class Module
{
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array('namespaces' => array(
                __NAMESPACE__ => __DIR__ . '/src/',
            )),
        );
    }

    /**
     * Retrieve module configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getServiceConfig()
    {
        return array('factories' => array(
            'ZF\Rpc\OptionsListener' => function ($services) {
                $rpcConfig = array();
                if ($services->has('Config')) {
                    $config = $services->get('Config');
                    if (isset($config['zf-rpc'])) {
                        $rpcConfig = $config['zf-rpc'];
                    }
                }
                return new OptionsListener($rpcConfig);
            },
        ));
    }

    public function onBootstrap($e)
    {
        $app      = $e->getApplication();
        $services = $app->getServiceManager();

        // Attach OptionsListener
        $optionsListener = $services->get('ZF\Rpc\OptionsListener');
        $optionsListener->attach($app->getEventManager());

        // Setup json strategy
        $strategy = $services->get('ViewJsonStrategy');
        $view     = $services->get('ViewManager')->getView();
        $strategy->attach($view->getEventManager());
    }
}
