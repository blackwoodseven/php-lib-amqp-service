<?php
namespace BlackwoodSeven\Tests\AmqpService;

use Pimple\Container;
use BlackwoodSeven\AmqpService\ServiceProvider;
use PhpAmqpLib\Message\AMQPMessage;

class ServiceProviderUnitTest extends \PHPUnit_Framework_TestCase
{
    public function mockContainer()
    {
        $container = new Container();
        $container->register(new ServiceProvider());
        $container['amqp.connection'] = $this->getMockBuilder('PhpAmqpLib\Connection\AMQPLazyConnection')
            ->disableOriginalConstructor()
            ->getMock();
        $container['amqp.channel'] = $this->getMockBuilder('PhpAmqpLib\Channel\AMQPChannel')
            ->disableOriginalConstructor()
            ->getMock();
        $container['amqp.connection']->method('channel')->willReturn($container['amqp.channel']);
        return $container;
    }

    public function testQueueUninitialized()
    {
        $app = $this->mockContainer();

        $app['amqp.options'] = [
            'product' => 'test',
            'dsn' => 'tcp://none:none@localhost:1234/novhost',
            'queues' => [
                'testqueue' => ['bindings' => ['testexchange']]
            ],
        ];

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
    }

    public function testQueueListenOnce()
    {
        $app = $this->mockContainer();

        $app['amqp.options'] = [
            'product' => 'test',
            'dsn' => 'tcp://none:none@localhost:1234/novhost',
            'exchanges' => [
                'testexchange',
            ],
            'queues' => [
                'testqueue' => ['bindings' => ['testexchange' => ['#']]]
            ],
        ];

        $app['amqp.channel']
            ->expects($this->once())
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

        $app['amqp.queue']->listenOnce(function () {});
        $app['amqp.queues']['testqueue']->listenOnce(function () {});
    }

    public function testExchangePublish()
    {
        $app = $this->mockContainer();

        $app['amqp.options'] = [
            'product' => 'test',
            'dsn' => 'tcp://none:none@localhost:1234/novhost',
            'exchanges' => [
                'testexchange',
            ],
        ];

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

        $app['amqp.exchange']->publish(new AMQPMessage(['message' => 'test']), 'routing.key');
        $app['amqp.exchanges']['testexchange']->publish(new AMQPMessage(['message' => 'test']), 'routing.key');
    }
}
