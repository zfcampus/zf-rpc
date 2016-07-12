<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Rpc\Factory;

use Interop\Container\ContainerInterface;
use ZF\Rpc\OptionsListener;

class OptionsListenerFactory
{
    /**
     * @param ContainerInterface $container
     * @return OptionsListener
     */
    public function __invoke(ContainerInterface $container)
    {
        return new OptionsListener($this->getConfig($container));
    }

    /**
     * Attempt to marshal configuration from the "config" service.
     *
     * @param ContainerInterface $container
     * @return array
     */
    private function getConfig(ContainerInterface $container)
    {
        if (! $container->has('config')) {
            return [];
        }

        $config = $container->get('config');
        if (! isset($config['zf-rpc'])) {
            return [];
        }

        return $config['zf-rpc'];
    }
}
