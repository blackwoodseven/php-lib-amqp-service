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

    public function testTopologyEmpty()
    {
        $app = $this->initApp();

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

        $app['amqp.exchanges'] = [];
        $app['amqp.options'] = ['product' => 'unittest'];
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

}
