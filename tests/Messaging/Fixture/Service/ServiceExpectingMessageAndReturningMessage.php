<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Service;

use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class ServiceExpectingMessageAndReturningMessage
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceExpectingMessageAndReturningMessage
{
    /**
     * @var mixed
     */
    private $newPayload;

    /**
     * ServiceExpectingMessageAndReturningMessage constructor.
     * @param $newPayload
     */
    private function __construct($newPayload)
    {
        $this->newPayload = $newPayload;
    }

    public static function create(string $newPayload) : self
    {
        return new self($newPayload);
    }

    public function send(Message $message) : Message
    {
        return MessageBuilder::fromMessage($message)
                ->setPayload($this->newPayload)
                ->build();
    }
}