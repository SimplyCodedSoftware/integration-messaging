<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Handler;

use SimplyCodedSoftware\Messaging\Message;

/**
 * Class ReplyMessageProducer
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class FakeReplyMessageProducer implements \SimplyCodedSoftware\Messaging\Handler\MessageProcessor
{
    private $replyData;

    /**
     * ReplyMessageProducer constructor.
     * @param $replyData
     */
    private function __construct($replyData)
    {
        $this->replyData = $replyData;
    }

    public static function create($replyData) : self
    {
        return new self($replyData);
    }

    /**
     * @inheritDoc
     */
    public function processMessage(Message $message)
    {
        return $this->replyData;
    }

    public function __toString()
    {
        return self::class;
    }
}