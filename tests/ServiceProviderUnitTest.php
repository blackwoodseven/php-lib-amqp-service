<?php
namespace BlackwoodSeven\Tests\AmqpService;

use Silex\Application;
use BlackwoodSeven\AmqpService\ServiceProvider;
use PhpAmqpLib\Connection\AMQPChannel;

class ServiceProviderUnitTest extends \PHPUnit_Framework_TestCase
{
    public function testRegistration()
    {
    }

    public function mockChannel()
    {
        return $this->getMockBuilder(AMQPChannel::class)
            ->setMethods(['exchange_declare', 'queue_declare', 'queue_bind'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testTopology()
    {
        $app = new Application();
        $serviceProvider = new ServiceProvider();


        $app['amqp_service.ensure_topology'] = false;
        $app['amqp_service.definitions'] = [
            'exchanges' => [
                'test_exchange' => [
                    'type' => 'topic',
                ],
            ],
        ];
        $app['amqp_service.channel'] = $this->mockChannel();
        $app['amqp_service.channel']
            ->expects($this->never())
            ->method('exchange_declare')
            ->will($this->returnValue(true));
        $app['amqp_service.channel']
            ->expects($this->never())
            ->method('queue_declare')
            ->will($this->returnValue(true));
        $app['amqp_service.channel']
            ->expects($this->never())
            ->method('queue_bind')
            ->will($this->returnValue(true));
        $serviceProvider->boot($app);


        $app['amqp_service.ensure_topology'] = true;
        $app['amqp_service.definitions'] = [];
        $app['amqp_service.channel'] = $this->mockChannel();
        $app['amqp_service.channel']
            ->expects($this->never())
            ->method('exchange_declare')
            ->will($this->returnValue(true));
        $app['amqp_service.channel']
            ->expects($this->never())
            ->method('queue_declare')
            ->will($this->returnValue(true));
        $app['amqp_service.channel']
            ->expects($this->never())
            ->method('queue_bind')
            ->will($this->returnValue(true));
        $serviceProvider->boot($app);


        $app['amqp_service.ensure_topology'] = true;
        $app['amqp_service.definitions'] = [
            'exchanges' => [
                'test_exchange' => [
                    'type' => 'topic',
                ],
            ],
        ];
        $app['amqp_service.channel'] = $this->mockChannel();
        $app['amqp_service.channel']
            ->expects($this->once())
            ->method('exchange_declare')
            ->will($this->returnValue(true));
        $app['amqp_service.channel']
            ->expects($this->never())
            ->method('queue_declare')
            ->will($this->returnValue(true));
        $app['amqp_service.channel']
            ->expects($this->never())
            ->method('queue_bind')
            ->will($this->returnValue(true));
        $serviceProvider->boot($app);

        $app['amqp_service.ensure_topology'] = true;
        $app['amqp_service.definitions'] = [
            'queues' => [
                'test_queue' => [
                    'arguments' => [],
                    'bindings' => [
                        'test_exchange' => ['routingkey1.test'],
                    ],
                ],
            ],
        ];
        $app['amqp_service.channel'] = $this->mockChannel();
        $app['amqp_service.channel']
            ->expects($this->never())
            ->method('exchange_declare')
            ->will($this->returnValue(true));
        $app['amqp_service.channel']
            ->expects($this->once())
            ->method('queue_declare')
            ->will($this->returnValue(true));
        $app['amqp_service.channel']
            ->expects($this->once())
            ->method('queue_bind')
            ->will($this->returnValue(true));
        $serviceProvider->boot($app);
    }
}