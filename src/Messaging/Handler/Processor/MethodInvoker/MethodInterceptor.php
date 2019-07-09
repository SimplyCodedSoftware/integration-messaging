<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException;
use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class Interceptor
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodInterceptor implements InterceptorWithPointCut
{
    const DEFAULT_PRECEDENCE = 1;

    /**
     * @var string
     */
    private $interceptorName;
    /**
     * @var MessageHandlerBuilderWithOutputChannel
     */
    private $messageHandler;
    /**
     * @var int
     */
    private $precedence;
    /**
     * @var Pointcut
     */
    private $pointcut;
    /**
     * @var InterfaceToCall
     */
    private $interceptorInterfaceToCall;

    /**
     * Interceptor constructor.
     * @param string $interceptorName
     * @param InterfaceToCall $interceptorInterfaceToCall
     * @param MessageHandlerBuilderWithOutputChannel $messageHandler
     * @param int $precedence
     * @param Pointcut $pointcut
     */
    private function __construct(string $interceptorName, InterfaceToCall $interceptorInterfaceToCall, MessageHandlerBuilderWithOutputChannel $messageHandler, int $precedence, Pointcut $pointcut)
    {
        $this->messageHandler = $messageHandler;
        $this->precedence = $precedence;
        $this->pointcut = $pointcut;
        $this->interceptorName = $interceptorName;
        $this->interceptorInterfaceToCall = $interceptorInterfaceToCall;
    }

    /**
     * @param string $interceptorName
     * @param InterfaceToCall $interceptorInterfaceToCall
     * @param MessageHandlerBuilderWithOutputChannel $messageHandler
     * @param int $precedence
     * @param string $pointcut
     * @return MethodInterceptor
     */
    public static function create(string $interceptorName, InterfaceToCall $interceptorInterfaceToCall, MessageHandlerBuilderWithOutputChannel $messageHandler, int $precedence, string $pointcut)
    {
        return new self($interceptorName, $interceptorInterfaceToCall, $messageHandler, $precedence, Pointcut::createWith($pointcut));
    }

    /**
     * @param InterfaceToCall $interfaceToCall
     * @param object[] $endpointAnnotations
     * @return bool
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function doesItCutWith(InterfaceToCall $interfaceToCall, iterable $endpointAnnotations): bool
    {
        return $this->pointcut->doesItCut($interfaceToCall, $endpointAnnotations);
    }

    /**
     * @inheritDoc
     */
    public function getInterceptingObject()
    {
        return $this->messageHandler;
    }

    /**
     * @param InterfaceToCall $interceptedInterface
     * @param array $endpointAnnotations
     * @return static
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function addInterceptedInterfaceToCall(InterfaceToCall $interceptedInterface, array $endpointAnnotations): self
    {
        $clone = clone $this;
        $interceptedMessageHandler = clone $clone->messageHandler;

        if ($interceptedMessageHandler instanceof MessageHandlerBuilderWithParameterConverters) {
            $parameterConverters = array_merge(
                [InterceptorConverterBuilder::create($interceptedInterface, $endpointAnnotations)],
                MethodInvoker::createDefaultMethodParameters($this->interceptorInterfaceToCall, $interceptedMessageHandler->getParameterConverters(), false)
            );

            $interceptedMessageHandler->withMethodParameterConverters($parameterConverters);
        }
        $clone->messageHandler = $interceptedMessageHandler;

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function hasName(string $name): bool
    {
        return $this->interceptorName === $name;
    }

    /**
     * @return string
     */
    public function getInterceptorName(): string
    {
        return $this->interceptorName;
    }

    /**
     * @return int
     */
    public function getPrecedence(): int
    {
        return $this->precedence;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "{$this->interceptorName}.{$this->messageHandler}";
    }

    /**
     * @return MessageHandlerBuilderWithOutputChannel
     */
    public function getMessageHandler(): MessageHandlerBuilderWithOutputChannel
    {
        return $this->messageHandler;
    }
}