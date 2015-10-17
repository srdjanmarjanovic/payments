<?php

namespace ActiveCollab\Payments\Test;

use ActiveCollab\Payments\Customer\CustomerInterface;
use ActiveCollab\Payments\Customer\Customer;
use ActiveCollab\Payments\Dispatcher\DispatcherInterface;
use ActiveCollab\Payments\Gateway\GatewayInterface;
use ActiveCollab\Payments\Order\OrderInterface;
use ActiveCollab\Payments\Order\Order;
use ActiveCollab\Payments\OrderItem\OrderItem;
use ActiveCollab\Payments\Order\Refund\RefundInterface;
use ActiveCollab\Payments\Subscription\Subscription;
use ActiveCollab\Payments\Subscription\SubscriptionInterface;
use ActiveCollab\Payments\Test\Fixtures\ExampleOffsiteGateway;
use ActiveCollab\DateValue\DateTimeValue;

/**
 * @package ActiveCollab\Payments\Test
 */
class DispatcherTest extends TestCase
{
    /**
     * @var ExampleOffsiteGateway
     */
    protected $gateway;

    /**
     * @var CustomerInterface
     */
    protected $customer;

    /**
     * @var DateTimeValue
     */
    protected $timestamp;

    /**
     * @var OrderInterface
     */
    protected $order;

    /**
     * @var SubscriptionInterface
     */
    protected $subscription;

    /**
     * Set up test environment
     */
    public function setUp()
    {
        parent::setUp();

        $this->gateway = new ExampleOffsiteGateway($this->dispatcher);
        $this->customer = new Customer('John Doe', 'john@example.com');
        $this->timestamp = new DateTimeValue('2015-10-15');
        $this->order = new Order($this->customer, '2015-01', $this->timestamp, 'USD', 1200, [
            new OrderItem('Expensive product', 1, 1000),
            new OrderItem('Not so expensive product', 2, 100),
        ]);

        $this->subscription = new Subscription($this->customer, '2015-01', $this->timestamp, SubscriptionInterface::MONTHLY, 'USD', 25, [
            new OrderItem('Monthly SaaS cost', 1, 25),
        ]);
    }

    /**
     * Tear down test environment
     */
    public function tearDown()
    {
        $this->gateway = $this->customer = $this->timestamp = $this->order = null;

        parent::tearDown();
    }

    /**
     * Test if order completed triggers an event
     */
    public function testOrderCompletedTriggersAnEvent()
    {
        $event_triggered = false;

        $this->dispatcher->listen(DispatcherInterface::ON_ORDER_COMPLETED, function(GatewayInterface $gateway, OrderInterface $order) use (&$event_triggered) {
            $this->assertInstanceOf(ExampleOffsiteGateway::class, $gateway);
            $this->assertInstanceOf(OrderInterface::class, $order);

            $this->assertEquals($this->order->getReference(), $order->getReference());

            $event_triggered = true;
        });

        $this->gateway->triggerOrderCompleted($this->order);
        $this->assertTrue($event_triggered);
    }

    /**
     * Test if order refund properly triggers an event
     */
    public function testOrderRefundedTriggersAnEvent()
    {
        $event_triggered = false;

        $this->dispatcher->listen(DispatcherInterface::ON_ORDER_REFUNDED, function(GatewayInterface $gateway, OrderInterface $order, RefundInterface $refund) use (&$event_triggered) {
            $this->assertInstanceOf(ExampleOffsiteGateway::class, $gateway);
            $this->assertInstanceOf(RefundInterface::class, $refund);
            $this->assertInstanceOf(OrderInterface::class, $order);

            $this->assertEquals($refund->getReference(), $order->getReference());
            $this->assertEquals($refund->getTotal(), $order->getTotal());

            $this->assertFalse($refund->isPartial());

            $event_triggered = true;
        });

        $this->gateway->triggerOrderRefunded($this->order, $this->timestamp);
        $this->assertTrue($event_triggered);
    }

    /**
     * Test if partial order refund properly triggers an event
     */
    public function testOrderPartiallyRefundedTriggersAnEvent()
    {
        $event_triggered = false;

        $this->dispatcher->listen(DispatcherInterface::ON_ORDER_PARTIALLY_REFUNDED, function(GatewayInterface $gateway, OrderInterface $order, RefundInterface $refund) use (&$event_triggered) {
            $this->assertInstanceOf(ExampleOffsiteGateway::class, $gateway);
            $this->assertInstanceOf(RefundInterface::class, $refund);
            $this->assertInstanceOf(OrderInterface::class, $order);

            $this->assertEquals($refund->getReference(), $order->getReference());
            $this->assertGreaterThan($refund->getTotal(), $order->getTotal());

            $this->assertInternalType('array', $refund->getItems());
            $this->assertCount(1, $refund->getItems());
            $this->assertEquals('Expensive product', $refund->getItems()[0]->getDescription());

            $this->assertTrue($refund->isPartial());

            $event_triggered = true;
        });

        $this->gateway->triggerOrderPartiallyRefunded($this->order, [
            new OrderItem('Expensive product', 1, 1000),
        ], $this->timestamp);

        $this->assertTrue($event_triggered);
    }

    /**
     * Test if subscription created triggers an event
     */
    public function testSubscriptionActivatedTriggersAnEvent()
    {
        $event_triggered = false;

        $this->dispatcher->listen(DispatcherInterface::ON_SUBSCRIPTION_ACTIVATED, function(GatewayInterface $gateway, SubscriptionInterface $subscription) use (&$event_triggered) {
            $this->assertInstanceOf(ExampleOffsiteGateway::class, $gateway);
            $this->assertInstanceOf(Subscription::class, $subscription);

            $this->assertEquals($this->subscription->getReference(), $subscription->getReference());

            $event_triggered = true;
        });

        $this->gateway->triggerSubscriptionActivated($this->subscription);

        $this->assertTrue($event_triggered);
    }
}