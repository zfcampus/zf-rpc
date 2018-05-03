<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Rpc\Factory;

use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ProphecyInterface;
use ZF\Rpc\Factory\OptionsListenerFactory;
use ZF\Rpc\OptionsListener;

class OptionsListenerFactoryTest extends TestCase
{
    /**
     * @var ContainerInterface|ProphecyInterface
     */
    private $container;

    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function testWillCreateOptionsListenerWithEmptyConfigWhenConfigServiceIsNotPresent()
    {
        $this->container->has('config')->willReturn(false);
        $factory = new OptionsListenerFactory();

        $listener = $factory($this->container->reveal());
        $this->assertInstanceOf(OptionsListener::class, $listener);
        $this->assertAttributeEquals([], 'config', $listener);
    }

    public function testWillCreateOptionsListenerWithEmptyConfigWhenNoRpcConfigPresent()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn(['foo' => 'bar']);
        $factory = new OptionsListenerFactory();

        $listener = $factory($this->container->reveal());
        $this->assertInstanceOf(OptionsListener::class, $listener);
        $this->assertAttributeEquals([], 'config', $listener);
    }

    public function testWillCreateOptionsListenerWithRpcConfigWhenPresent()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([
            'zf-rpc' => [
                'foo' => 'bar',
            ],
        ]);
        $factory = new OptionsListenerFactory();

        $listener = $factory($this->container->reveal());
        $this->assertInstanceOf(OptionsListener::class, $listener);
        $this->assertAttributeEquals(['foo' => 'bar'], 'config', $listener);
    }
}
