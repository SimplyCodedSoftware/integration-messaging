<?php

namespace SimplyCodedSoftware\Messaging\Handler\Transformer;

use SimplyCodedSoftware\Messaging\Conversion\MediaType;
use SimplyCodedSoftware\Messaging\Handler\MessageProcessor;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class TransformerMessageProcessor
 * @package Messaging\Handler\Transformer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class TransformerMessageProcessor implements MessageProcessor
{
    /**
     * @var MethodInvoker
     */
    private $methodInvoker;

    /**
     * TransformerMessageProcessor constructor.
     * @param MethodInvoker $methodInvoker
     */
    private function __construct(MethodInvoker $methodInvoker)
    {
        $this->methodInvoker = $methodInvoker;
    }

    /**
     * @param MethodInvoker $methodInvoker
     * @return TransformerMessageProcessor
     */
    public static function createFrom(MethodInvoker $methodInvoker) : self
    {
        return new self($methodInvoker);
    }

    /**
     * @inheritDoc
     */
    public function processMessage(Message $message)
    {
        $reply = $this->methodInvoker->processMessage($message);
        $replyBuilder = MessageBuilder::fromMessage($message);

        if (is_null($reply)) {
            return null;
        }

        if (is_array($reply)) {
            if (is_array($message->getPayload())) {
                $reply = $replyBuilder
                    ->setMultipleHeaders($reply)
                    ->build();
            }else {
                $reply = $replyBuilder
                    ->setMultipleHeaders($reply)
                    ->build();
            }
        }else if (!($reply instanceof Message)) {
            $reply = $replyBuilder
                ->setPayload($reply)
                ->setContentType(MediaType::createApplicationXPHPObjectWithTypeParameter($this->methodInvoker->getInterfaceToCall()->getReturnType()->toString()))
                ->build();
        }

        return $reply;
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return (string)$this->methodInvoker;
    }
}