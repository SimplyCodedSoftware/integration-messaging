<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Handler;

use SimplyCodedSoftware\Messaging\Handler\MessageProcessor;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Class NoReplyMessageProducer
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class NoReplyMessageProducer implements MessageProcessor
{
    private $wasCalled = false;

    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function processMessage(Message $message)
    {
        $this->wasCalled = true;
        return null;
    }

    public function wasCalled() : bool
    {
        return $this->wasCalled;
    }

    public function __toString()
    {
        return self::class;
    }
}