<?php

namespace ActiveCollab\Payments\Order\Refund;

use ActiveCollab\Payments\Gateway\GatewayInterface;
use ActiveCollab\Payments\Order\OrderInterface;
use ActiveCollab\Payments\OrderItem\OrderItemInterface;
use ActiveCollab\DateValue\DateTimeValueInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * @package ActiveCollab\Payments\Refund
 */
class Refund implements RefundInterface
{
    /**
     * @var GatewayInterface
     */
    private $gateway;

    /**
     * @var string
     */
    private $refund_id;

    /**
     * @var string
     */
    private $reference;

    /**
     * @var DateTimeValueInterface
     */
    private $timestamp;

    /**
     * @var float
     */
    private $total = 0.0;

    /**
     * @var OrderItemInterface[]
     */
    private $items;

    /**
     * @var string
     */
    private $our_identifier = '';

    /**
     * Construct a new refund instance
     *
     * @param string                 $refund_id
     * @param string                 $reference
     * @param DateTimeValueInterface $timestamp
     * @param float                  $total
     */
    public function __construct($refund_id, $reference, DateTimeValueInterface $timestamp, $total)
    {
        if (empty($refund_id)) {
            throw new InvalidArgumentException('Refund # is required');
        }

        if (empty($reference)) {
            throw new InvalidArgumentException('Order # is required');
        }

        if ($total <= 0) {
            throw new InvalidArgumentException("Empty or credit orders are not supported");
        }

        $this->refund_id = $refund_id;
        $this->reference = $reference;
        $this->timestamp = $timestamp;
        $this->total = (float) $total;
    }

    /**
     * Return parent gateway
     *
     * @return GatewayInterface
     */
    public function &getGateway()
    {
        return $this->gateway;
    }

    /**
     * Set parent gateway
     *
     * @param  GatewayInterface $gateway
     * @return $this
     */
    public function &setGateway(GatewayInterface &$gateway)
    {
        $this->gateway = $gateway;

        return $this;
    }

    /**
     * Return refund ID
     *
     * @return string
     */
    public function getRefundId()
    {
        return $this->refund_id;
    }

    /**
     * Return reference (order ID)
     *
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Return order by order ID
     *
     * @return OrderInterface
     */
    public function getOrder()
    {
        if ($this->gateway instanceof GatewayInterface) {
            return $this->gateway->getByOrderReference($this->getReference());
        }

        throw new RuntimeException('Gateway is not set');
    }

    /**
     * Return date and time when this order was made
     *
     * @return DateTime
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @return float
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @return \ActiveCollab\Payments\OrderItem\OrderItemInterface[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Set refund items, if refund was by line item
     *
     * @param  \ActiveCollab\Payments\OrderItem\OrderItemInterface[] $value
     * @return $this
     */
    public function &setItems(array $value)
    {
        $this->items = $value;

        return $this;
    }

    /**
     * Return our internal order indetifier (if present)
     *
     * @return string
     */
    public function getOurIdentifier()
    {
        return $this->our_identifier;
    }

    /**
     * Set our identifier
     *
     * @param  string $value
     * @return $this
     */
    public function &setOurIdentifier($value)
    {
        $this->our_identifier = trim($value);

        return $this;
    }

    /**
     * Return true if this refund is partial
     *
     * @return boolean
     */
    public function isPartial()
    {
        return $this->getTotal() < $this->getOrder()->getTotal();
    }
}
