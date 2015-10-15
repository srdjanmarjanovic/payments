<?php

namespace ActiveCollab\Payments\Test\Fixtures;

use ActiveCollab\Payments\DispatcherInterface;
use ActiveCollab\Payments\Gateway;
use ActiveCollab\Payments\Order\OrderInterface;
use ActiveCollab\Payments\Refund\RefundInterface;
use ActiveCollab\Payments\Refund\Refund;
use InvalidArgumentException;
use DateTime;
use DateTimeZone;

/**
 * @package ActiveCollab\Payments\Test\Fixtures
 */
class ExampleOffsiteGateway extends Gateway
{
    /**
     * @var OrderInterface[]
     */
    private $orders = [];

    /**
     * @var RefundInterface[]
     */
    private $refunds = [];

    /**
     * @param DispatcherInterface $dispatcher
     */
    public function __construct(DispatcherInterface &$dispatcher)
    {
        $this->setDispatcher($dispatcher);
    }

    /**
     * Return order by order ID
     *
     * @param  string         $order_id
     * @return OrderInterface
     */
    public function getOrderById($order_id)
    {
        if (isset($this->orders[$order_id])) {
            return $this->orders[$order_id];
        }

        throw new InvalidArgumentException("Order #{$order_id} not found");
    }

    /**
     * Return refund by refund ID
     *
     * @param  string          $refund_id
     * @return RefundInterface
     */
    public function getRefundByid($refund_id)
    {
        if (isset($this->refunds[$refund_id])) {
            return $this->refunds[$refund_id];
        }

        throw new InvalidArgumentException("Refund #{$refund_id} not found");
    }

    /**
     * Trigger product order completed
     *
     * @param OrderInterface $order
     */
    public function triggerOrderCompleted(OrderInterface $order)
    {
        $this->orders[$order->getOrderId()] = $order;

        $this->getDispatcher()->trigger(DispatcherInterface::ON_ORDER_COMPLETED, $this, $order);
    }

    /**
     * Trigger product order failed
     *
     * @param OrderInterface $order
     */
    public function triggerOrderFailed(OrderInterface $order)
    {
        $this->orders[$order->getOrderId()] = $order;

        $this->getDispatcher()->trigger(DispatcherInterface::ON_ORDER_FAILED, $this, $order);
    }

    /**
     * Trigger order has been fully refunded
     *
     * @param OrderInterface $order
     * @param DateTime|null  $timestamp
     */
    public function triggerOrderRefunded(OrderInterface $order, DateTime $timestamp = null)
    {
        $this->orders[$order->getOrderId()] = $order;

        if (empty($timestamp)) {
            $timestamp = new DateTime('now', new DateTimeZone('UTC'));
        }

        $refund = new Refund($order->getOrderId() . '-X', $order->getOrderId(), $timestamp, $order->getTotal());
        $refund->setGateway($this);

        $this->refunds[$refund->getRefundId()] = $refund;

        $this->getDispatcher()->trigger(DispatcherInterface::ON_ORDER_REFUNDED, $this, $refund, $order);
    }

    /**
     * Trigger order has been partially refunded
     *
     * @param OrderInterface $order
     */
    public function triggerOrderPartiallyRefunded(OrderInterface $order)
    {
        $this->getDispatcher()->trigger(DispatcherInterface::ON_ORDER_PARTIALLY_REFUNDED, $this, $order);
    }
}
