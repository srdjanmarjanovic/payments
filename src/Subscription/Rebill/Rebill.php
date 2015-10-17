<?php

namespace ActiveCollab\Payments\Subscription\Rebill;

use ActiveCollab\DateValue\DateTimeValueInterface;
use ActiveCollab\Payments\Traits\Gateway;
use ActiveCollab\Payments\Traits\Timestamp;
use ActiveCollab\Payments\Subscription\SubscriptionEvent\Implementation as SubscriptionEventImplementation;
use InvalidArgumentException;

/**
 * @package ActiveCollab\Payments\Subscription\FailedPayment
 */
class Rebill implements RebillInterface
{
    use Gateway, Timestamp, SubscriptionEventImplementation;

    /**
     * Construct a new refund instance
     *
     * @param string                 $subscription_reference
     * @param DateTimeValueInterface $timestamp
     */
    public function __construct($subscription_reference, DateTimeValueInterface $timestamp)
    {
        if (empty($subscription_reference)) {
            throw new InvalidArgumentException('Subscription # is required');
        }

        $this->subscription_reference = $subscription_reference;
        $this->timestamp = $timestamp;
    }
}
