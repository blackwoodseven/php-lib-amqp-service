<?php
namespace BlackwoodSeven\Tests\AmqpService;

use Silex\Application;
use BlackwoodSeven\AmqpService\ServiceProvider;
use PhpAmqpLib\Connection\AMQPChannel;

class ServiceProviderUnitTest extends \PHPUnit_Framework_TestCase
{
    public function initApp()
    {
        $app = new Application();
        $app->register(new ServiceProvider());
        $app['amqp.channel'] = $this->getMockBuilder('PhpAmqpLib\Channel\AMQPChannel')
            ->disableOriginalConstructor()
            ->getMock();
        return $app;
    }

    public function testTopologyIgnore()
    {
        $app = $this->initApp();

        $app['amqp.ensure_topology'] = false;
        $app['amqp.exchanges'] = [
            'test_exchange' => [
                'type' => 'topic',
            ],
        ];
        $app['amqp.queues'] = [];
        $app['amqp.channel']
            ->expects($this->never())
            ->method('exchange_declare')
            ->will($this->returnValue(true));
        $app['amqp.channel']
            ->expects($this->never())
            ->method('queue_declare')
            ->will($this->returnValue(true));
        $app['amqp.channel']
            ->expects($this->never())
            ->method('queue_bind')
            ->will($this->returnValue(true));
        $app->boot();

    }

    public function testTopologyEmpty()
    {
        $app = $this->initApp();

        $app['amqp.ensure_topology'] = true;
        $app['amqp.exchanges'] = [];
        $app['amqp.queues'] = [];
        $app['amqp.channel']
            ->expects($this->never())
            ->method('exchange_declare')
            ->will($this->returnValue(true));
        $app['amqp.channel']
            ->expects($this->never())
            ->method('queue_declare')
            ->will($this->returnValue(true));
        $app['amqp.channel']
            ->expects($this->never())
            ->method('queue_bind')
            ->will($this->returnValue(true));
        $app->boot();
    }

    public function testTopologyExchange()
    {
        $app = $this->initApp();

        $app['amqp.ensure_topology'] = true;
        $app['amqp.exchanges'] = [
            'test_exchange' => [
                'type' => 'topic',
            ],
        ];
        $app['amqp.queues'] = [];
        $app['amqp.channel']
            ->expects($this->once())
            ->method('exchange_declare')
            ->will($this->returnValue(true));
        $app['amqp.channel']
            ->expects($this->never())
            ->method('queue_declare')
            ->will($this->returnValue(true));
        $app['amqp.channel']
            ->expects($this->never())
            ->method('queue_bind')
            ->will($this->returnValue(true));
        $app->boot();
    }

    public function testTopologyQueue()
    {
        $app = $this->initApp();

        $app['amqp.ensure_topology'] = true;
        $app['amqp.exchanges'] = [];
        $app['amqp.queues'] = [
            'test_queue' => [
                'arguments' => [],
                'bindings' => [
                    'test_exchange' => ['routingkey1.test'],
                ],
            ],
        ];
        $app['amqp.channel']
            ->expects($this->never())
            ->method('exchange_declare')
            ->will($this->returnValue(true));
        $app['amqp.channel']
            ->expects($this->once())
            ->method('queue_declare')
            ->will($this->returnValue(true));
        $app['amqp.channel']
            ->expects($this->once())
            ->method('queue_bind')
            ->will($this->returnValue(true));
        $app->boot();
    }

    public function testHelpers()
    {
        $app = $this->initApp();

        $app['amqp.ensure_topology'] = false;
        $app['amqp.exchanges'] = [
            'test_exchange_2' => [],
            'test_exchange_3' => [],
            'test_exchange_1' => [],
        ];
        $app['amqp.queues'] = [
            'test_queue_2' => [],
            'test_queue_3' => [],
            'test_queue_1' => [],
        ];

        $app->boot();

        $this->assertEquals('test_exchange_2', $app['amqp.exchange_name'], 'Wrong exchange name returned');
        $this->assertEquals('test_queue_2', $app['amqp.queue_name'], 'Wrong queue name returned');
    }
}
