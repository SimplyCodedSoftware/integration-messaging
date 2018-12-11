<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Behat\Ordering;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class OrderProcessor
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Behat\Ordering
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class OrderProcessor
{
    public function processOrder(Order $order) : OrderConfirmation
    {
        if (!$this->isCorrectOrder($order)) {
            throw new \RuntimeException("Order is not correct!");
        }

        return OrderConfirmation::fromOrder($order);
    }

    /**
     * @param Uuid[]|array $ids
     * @return OrderConfirmation[]|array
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function buyMultiple(array $ids) : array
    {
        Assert::allInstanceOfType($ids, UuidInterface::class);
        $orders = [];
        foreach ($ids as $id) {
            $orders[] = OrderConfirmation::createFromUuid($id);
        }

        return $orders;
    }

    /**
     * @param UuidInterface $id
     * @return OrderConfirmation
     */
    public function buyByName(UuidInterface $id) : OrderConfirmation
    {
        return OrderConfirmation::createFromUuid($id);
    }

    private function isCorrectOrder(Order $order) : bool
    {
        return $order->getProductName() === 'correct';
    }
}