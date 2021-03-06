<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Channel;

use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\PollableChannel;

/**
 * Class SimpleMessageChannelBuilder
 * @package SimplyCodedSoftware\Messaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SimpleMessageChannelBuilder implements MessageChannelBuilder
{
    /**
     * @var string
     */
    private $messageChannelName;
    /**
     * @var MessageChannel
     */
    private $messageChannel;
    /**
     * @var bool
     */
    private $isPollable;

    /**
     * SimpleMessageChannelBuilder constructor.
     * @param string $messageChannelName
     * @param MessageChannel $messageChannel
     * @param bool $isPollable
     */
    private function __construct(string $messageChannelName, MessageChannel $messageChannel, bool $isPollable)
    {
        $this->messageChannelName = $messageChannelName;
        $this->messageChannel = $messageChannel;
        $this->isPollable = $isPollable;
    }

    /**
     * @param string $messageChannelName
     * @param MessageChannel $messageChannel
     * @return SimpleMessageChannelBuilder
     */
    public static function create(string $messageChannelName, MessageChannel $messageChannel) : self
    {
        return new self($messageChannelName, $messageChannel, $messageChannel instanceof PollableChannel);
    }

    /**
     * @param string $messageChannelName
     * @return SimpleMessageChannelBuilder
     */
    public static function createDirectMessageChannel(string $messageChannelName) : self
    {
        return self::create($messageChannelName, DirectChannel::create());
    }

    /**
     * @param string $messageChannelName
     * @return SimpleMessageChannelBuilder
     */
    public static function createPublishSubscribeChannel(string $messageChannelName) : self
    {
        return self::create($messageChannelName, PublishSubscribeChannel::create());
    }

    /**
     * @param string $messageChannelName
     * @return SimpleMessageChannelBuilder
     */
    public static function createQueueChannel(string $messageChannelName) : self
    {
        return self::create($messageChannelName, QueueChannel::create());
    }

    /**
     * @inheritDoc
     */
    public function isPollable(): bool
    {
        return $this->isPollable;
    }

    /**
     * @return string[] empty string means no required reference name exists
     */
    public function getRequiredReferenceNames() : array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getMessageChannelName(): string
    {
        return $this->messageChannelName;
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService) : MessageChannel
    {
        return $this->messageChannel;
    }

    public function __toString()
    {
        return (string)$this->messageChannel;
    }
}