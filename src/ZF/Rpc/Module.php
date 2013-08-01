<?php

namespace ZF\Rpc;

class Module
{
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }

    /**
     * Retrieve module configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/../../../config/module.config.php';
    }

    public function onBootstrap($e)
    {
        $sm = $e->getApplication()->getServiceManager();

        // setup json strategy (@todo This needs to be globalized)
        $strategy = $sm->get('ViewJsonStrategy');
        $view = $sm->get('ViewManager')->getView();
        $strategy->attach($view->getEventManager());
    }
}
