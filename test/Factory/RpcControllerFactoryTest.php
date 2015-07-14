<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Rpc\Factory;

use PHPUnit_Framework_TestCase as TestCase;
use ReflectionProperty;
use ZF\Rpc\Factory\RpcControllerFactory;

class RpcControllerFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services = $services = $this->prophesize('Zend\ServiceManager\ServiceLocatorInterface');
        $this->controllers = $this->prophesize('Zend\Mvc\Controller\ControllerManager');
        $this->controllers->getServiceLocator()->will(function () use ($services) {
            return $services->reveal();
        });
        $this->factory  = new RpcControllerFactory();
    }

    /**
     * @group 7
     */
    public function testWillPullNonCallableStaticCallableFromControllerManagerIfServiceIsPresent()
    {
        $config = array(
            'zf-rpc' => array(
                'Controller\Foo' => array(
                    'callable' => 'Foo::bar',
                ),
            ),
        );
        $this->services->has('Config')->willReturn(true);
        $this->services->get('Config')->willReturn($config);

        $foo = $this->prophesize('stdClass');
        $this->controllers->has('Foo')->willReturn(true);
        $this->controllers->get('Foo')->willReturn($foo->reveal());

        $controllers = $this->controllers->reveal();

        $this->assertTrue($this->factory->canCreateServiceWithName(
            $controllers,
            'Controller\Foo',
            'Controller\Foo'
        ));
        $controller = $this->factory->createServiceWithName(
            $controllers,
            'Controller\Foo',
            'Controller\Foo'
        );

        $this->assertInstanceOf('ZF\Rpc\RpcController', $controller);
        $this->assertAttributeSame(array($foo->reveal(), 'bar'), 'wrappedCallable', $controller);
    }

    /**
     * @group 7
     */
    public function testWillPullNonCallableStaticCallableFromServiceManagerIfServiceIsPresent()
    {
        $config = array(
            'zf-rpc' => array(
                'Controller\Foo' => array(
                    'callable' => 'Foo::bar',
                ),
            ),
        );
        $this->services->has('Config')->willReturn(true);
        $this->services->get('Config')->willReturn($config);

        $foo = $this->prophesize('stdClass');
        $this->services->has('Foo')->willReturn(true);
        $this->services->get('Foo')->willReturn($foo->reveal());

        $this->controllers->has('Foo')->willReturn(false);

        $controllers = $this->controllers->reveal();

        $this->assertTrue($this->factory->canCreateServiceWithName(
            $controllers,
            'Controller\Foo',
            'Controller\Foo'
        ));
        $controller = $this->factory->createServiceWithName(
            $controllers,
            'Controller\Foo',
            'Controller\Foo'
        );

        $this->assertInstanceOf('ZF\Rpc\RpcController', $controller);
        $this->assertAttributeSame(array($foo->reveal(), 'bar'), 'wrappedCallable', $controller);
    }

    /**
     * @group 7
     */
    public function testWillInstantiateCallableClassIfClassExists()
    {
        $config = array(
            'zf-rpc' => array(
                'Controller\Foo' => array(
                    'callable' => 'ZFTest\Rpc\Factory\TestAsset\Foo::bar',
                ),
            ),
        );
        $this->services->has('Config')->willReturn(true);
        $this->services->get('Config')->willReturn($config);

        $this->controllers->has('ZFTest\Rpc\Factory\TestAsset\Foo')->willReturn(false);
        $this->services->has('ZFTest\Rpc\Factory\TestAsset\Foo')->willReturn(false);

        $controllers = $this->controllers->reveal();

        $this->assertTrue($this->factory->canCreateServiceWithName(
            $controllers,
            'Controller\Foo',
            'Controller\Foo'
        ));
        $controller = $this->factory->createServiceWithName(
            $controllers,
            'Controller\Foo',
            'Controller\Foo'
        );

        $this->assertInstanceOf('ZF\Rpc\RpcController', $controller);

        $r = new ReflectionProperty($controller, 'wrappedCallable');
        $r->setAccessible(true);
        $callable = $r->getValue($controller);
        $this->assertInternalType('array', $callable);
        $this->assertInstanceOf(__NAMESPACE__ . '\TestAsset\Foo', $callable[0]);
        $this->assertEquals('bar', $callable[1]);
    }

    public function testReportsCannotCreateServiceIfConfigIsMissing()
    {
        $this->services->has('Config')->willReturn(false);
        $this->assertFalse($this->factory->canCreateServiceWithName(
            $this->controllers->reveal(),
            'Controller\Foo',
            'Controller\Foo'
        ));
    }

    public function testReportsCannotCreateServiceIfRpcConfigIsMissing()
    {
        $this->services->has('Config')->willReturn(true);
        $this->services->get('Config')->willReturn(array());
        $this->assertFalse($this->factory->canCreateServiceWithName(
            $this->controllers->reveal(),
            'Controller\Foo',
            'Controller\Foo'
        ));
    }

    public function testReportsCannotCreateServiceIfRpcConfigDoesNotContainServiceName()
    {
        $this->services->has('Config')->willReturn(true);
        $this->services->get('Config')->willReturn(array('zf-rpc' => array()));
        $this->assertFalse($this->factory->canCreateServiceWithName(
            $this->controllers->reveal(),
            'Controller\Foo',
            'Controller\Foo'
        ));
    }

    public function testReportsCannotCreateServiceIfRpcConfigForControllerIsNotArray()
    {
        $this->services->has('Config')->willReturn(true);
        $this->services->get('Config')->willReturn(array('zf-rpc' => array(
            'Controller\Foo' => true,
        )));
        $this->assertFalse($this->factory->canCreateServiceWithName(
            $this->controllers->reveal(),
            'Controller\Foo',
            'Controller\Foo'
        ));
    }

    public function testReportsCannotCreateServiceIfRpcConfigForControllerDoesNotContainCallableKey()
    {
        $this->services->has('Config')->willReturn(true);
        $this->services->get('Config')->willReturn(array('zf-rpc' => array(
            'Controller\Foo' => array(),
        )));
        $this->assertFalse($this->factory->canCreateServiceWithName(
            $this->controllers->reveal(),
            'Controller\Foo',
            'Controller\Foo'
        ));
    }

    public function invalidCallables()
    {
        return array(
            'null'       => array(null),
            'zero'       => array(0),
            'int'        => array(1),
            'zero-float' => array(0.0),
            'float'      => array(1.1),
            'array'      => array(array(true, false)),
            'object'     => array((object) array()),
        );
    }

    /**
     * @dataProvider invalidCallables
     */
    public function testServiceCreationFailsForInvalidCallable($callable)
    {
        $this->services->get('Config')->willReturn(array('zf-rpc' => array(
            'Controller\Foo' => array(
                'callable' => $callable,
            ),
        )));
        $this->setExpectedException('InvalidArgumentException', 'Unable to create');
        $this->factory->createServiceWithName(
            $this->controllers->reveal(),
            'Controller\Foo',
            'Controller\Foo'
        );
    }

    public function validCallbacks()
    {
        return array(
            'function'        => array('is_array'),
            'closure'         => array(function () {
            }),
            'invokable'       => array(new TestAsset\Invokable()),
            'instance-method' => array(array(new TestAsset\Foo(), 'bar')),
            'static-method'   => array(array(__NAMESPACE__ . '\TestAsset\Foo', 'baz')),
        );
    }

    /**
     * @dataProvider validCallbacks
     */
    public function testServiceCreationReturnsRpcControllerWrappingCallableForValidCallbacks($callable)
    {
        $this->services->get('Config')->willReturn(array('zf-rpc' => array(
            'Controller\Foo' => array(
                'callable' => $callable,
            ),
        )));
        $controller = $this->factory->createServiceWithName(
            $this->controllers->reveal(),
            'Controller\Foo',
            'Controller\Foo'
        );

        $this->assertInstanceOf('ZF\Rpc\RpcController', $controller);
        $this->assertAttributeSame($callable, 'wrappedCallable', $controller);
    }
}
