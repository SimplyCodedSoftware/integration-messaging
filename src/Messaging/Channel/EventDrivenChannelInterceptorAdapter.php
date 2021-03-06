<?php

namespace SimplyCodedSoftware\Messaging\Channel;

use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\SubscribableChannel;
use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class EventDrivenChannelInterceptorAdapter
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EventDrivenChannelInterceptorAdapter extends SendingInterceptorAdapter implements SubscribableChannel
{
    /**
     * @var SubscribableChannel
     */
    protected $messageChannel;

    /**
     * @inheritDoc
     */
    public function subscribe(MessageHandler $messageHandler): void
    {
        $this->messageChannel->subscribe($messageHandler);
    }

    /**
     * @inheritDoc
     */
    public function unsubscribe(MessageHandler $messageHandler): void
    {
        $this->messageChannel->unsubscribe($messageHandler);
    }

    /**
     * @inheritDoc
     */
    protected function initialize(MessageChannel $messageChannel): void
    {
        Assert::isSubclassOf($messageChannel, SubscribableChannel::class, "Event driven interceptor expects subscribable channel");

        $this->messageChannel = $messageChannel;
    }
}