<?php

namespace Fixture\Behat\Ordering;

/**
 * Class OrderConfirmation
 * @package Fixture\Behat\Ordering
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class OrderConfirmation
{
    /**
     * @var string
     */
    private $orderId;

    /**
     * Order constructor.
     * @param string $orderId
     */
    private function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * @param Order $order
     * @return OrderConfirmation
     */
    public static function fromOrder(Order $order) : self
    {
        return new self($order->getOrderId());
    }
}