<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Filter;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class MessageFilterBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Filter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageFilterBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters
{
    /**
     * @var ParameterConverterBuilder[]
     */
    private $parameterConverters = [];
    /**
     * @var string[]
     */
    private $requiredReferences = [];
    /**
     * @var string
     */
    private $referenceName;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var string
     */
    private $discardChannelName;
    /**
     * @var bool
     */
    private $throwExceptionOnDiscard = false;

    /**
     * MessageFilterBuilder constructor.
     *
     * @param string $referenceName
     * @param string $methodName
     */
    private function __construct(string $referenceName, string $methodName)
    {
        $this->referenceName     = $referenceName;
        $this->methodName        = $methodName;

        $this->initialize();
    }

    /**
     * @param string $referenceName
     * @param string $methodName
     *
     * @return MessageFilterBuilder
     */
    public static function createWithReferenceName(string $referenceName, string $methodName): self
    {
        return new self($referenceName, $methodName);
    }

    /**
     * @inheritDoc
     */
    public function registerRequiredReference(string $referenceName)
    {
        $this->requiredReferences[] = $referenceName;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return $this->requiredReferences;
    }

    /**
     * @inheritDoc
     */
    public function withMethodParameterConverters(array $methodParameterConverterBuilders): self
    {
        $this->parameterConverters = $methodParameterConverterBuilders;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getParameterConverters(): array
    {
        return $this->parameterConverters;
    }

    /**
     * @param string $discardChannelName
     *
     * @return MessageFilterBuilder
     */
    public function withDiscardChannelName(string $discardChannelName): self
    {
        $this->discardChannelName = $discardChannelName;

        return $this;
    }

    /**
     * @param bool $throwOnDiscard
     *
     * @return MessageFilterBuilder
     */
    public function withThrowingExceptionOnDiscard(bool $throwOnDiscard): self
    {
        $this->throwExceptionOnDiscard = $throwOnDiscard;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        $messageSelector = $referenceSearchService->get($this->referenceName);

        /** @var InterfaceToCall $interfaceToCall */
        $interfaceToCall = $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME)->getFor($messageSelector, $this->methodName);
        if (!$interfaceToCall->hasReturnValueBoolean()) {
            throw InvalidArgumentException::create("Object with reference {$this->referenceName} should return bool for method {$this->methodName} while using Message Filter");
        }

        $discardChannel = $this->discardChannelName ? $channelResolver->resolve($this->discardChannelName) : null;

        $serviceActivatorBuilder = ServiceActivatorBuilder::createWithDirectReference(
            new MessageFilter(
                MethodInvoker::createWith(
                    $messageSelector,
                    $this->methodName,
                    $this->parameterConverters,
                    $referenceSearchService
                ),
                $discardChannel,
                $this->throwExceptionOnDiscard
            ),
            "handle"
            )
            ->withInputChannelName($this->inputMessageChannelName)
            ->withOutputMessageChannel($this->outputMessageChannelName);

        return $serviceActivatorBuilder->build($channelResolver, $referenceSearchService);
    }


    private function initialize() : void
    {
        if ($this->referenceName) {
            $this->registerRequiredReference($this->referenceName);
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf("Message filter - %s:%s with name `%s` for input channel `%s`", $this->referenceName, $this->methodName, $this->getEndpointId(), $this->inputMessageChannelName);
    }
}