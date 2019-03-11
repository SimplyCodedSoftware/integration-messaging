<?php

namespace SimplyCodedSoftware\DomainModel;

use SimplyCodedSoftware\Messaging\Config\ReferenceTypeFromNameResolver;
use SimplyCodedSoftware\Messaging\Handler\Chain\ChainMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\Enricher\PropertyReaderAccessor;
use SimplyCodedSoftware\Messaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\Support\Assert;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class AggregateCallingCommandHandlerBuilder
 * @package SimplyCodedSoftware\DomainModel
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AggregateMessageHandlerBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters, MessageHandlerBuilderWithOutputChannel
{
    /**
     * @var string
     */
    private $aggregateClassName;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var array|ParameterConverterBuilder[]
     */
    private $methodParameterConverterBuilders = [];
    /**
     * @var string[]
     */
    private $requiredReferences = [];
    /**
     * @var bool
     */
    private $isCommandHandler;
    /**
     * @var AggregateRepositoryFactory[]
     */
    private $aggregateRepositoryFactories = [];
    /**
     * @var bool
     */
    private $isFactoryMethod;
    /**
     * @var bool
     */
    private $isVoidMethod;
    /**
     * @var string[]
     */
    private $messageIdentifierMapping;
    /**
     * @var ?string
     */
    private $expectedVersionPropertyName;

    /**
     * AggregateCallingCommandHandlerBuilder constructor.
     *
     * @param string $aggregateClassName
     * @param string $methodName
     * @param bool $isCommandHandler
     * @param string $handledMessageClassName
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function __construct(string $aggregateClassName, string $methodName, bool $isCommandHandler, string $handledMessageClassName)
    {
        $this->aggregateClassName = $aggregateClassName;
        $this->methodName = $methodName;
        $this->isCommandHandler = $isCommandHandler;

        $this->initialize($aggregateClassName, $handledMessageClassName);
    }

    /**
     * @param string $aggregateClassName
     * @param string $handledMessageClassName
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function initialize(string $aggregateClassName, string $handledMessageClassName): void
    {
        $interfaceToCall = InterfaceToCall::create($this->aggregateClassName, $this->methodName);
        $this->isFactoryMethod = $interfaceToCall->isStaticallyCalled();
        $this->isVoidMethod = $interfaceToCall->getReturnType()->isVoid();

        $reflectionClass = new \ReflectionClass($aggregateClassName);
        $aggregateDefaultIdentifiers = [];
        foreach ($reflectionClass->getProperties() as $property) {
            if (preg_match("*AggregateIdentifier*", $property->getDocComment())) {
                $aggregateDefaultIdentifiers[$property->getName()] = null;
            }
        }

        if (empty($aggregateDefaultIdentifiers)) {
            throw InvalidArgumentException::create("Aggregate {$aggregateClassName} has no identifiers defined. How you forgot to mark @AggregateIdentifier?");
        }

        $messageReflection = new \ReflectionClass($handledMessageClassName);
        $expectedVersionPropertyName = null;
        foreach ($messageReflection->getProperties() as $property) {
            if (preg_match("*TargetAggregateIdentifier*", $property->getDocComment())) {
                preg_match('#@TargetAggregateIdentifier\([^"]*"([^"]*)\"\)#', $property->getDocComment(), $matches);
                $mappingName = $property->getName();
                if (isset($matches[1])) {
                    $mappingName = trim($matches[1]);
                }

                $aggregateDefaultIdentifiers[$mappingName] = $property->getName();
            }
            if (preg_match("*AggregateExpectedVersion*", $property->getDocComment())) {
                $expectedVersionPropertyName = $property->getName();
            }
        }

        foreach ($aggregateDefaultIdentifiers as $aggregateIdentifierName => $aggregateIdentifierMappingKey) {
            if (is_null($aggregateIdentifierMappingKey)) {
                $mappingKey = null;
                foreach ($messageReflection->getProperties() as $property) {
                    if ($aggregateIdentifierName === $property->getName()) {
                        $mappingKey = $property->getName();
                    }
                }

                if (is_null($mappingKey)) {
                    throw new InvalidArgumentException("Can't find aggregate identifier mapping `{$aggregateIdentifierName}` for {$handledMessageClassName}. How you forgot to mark @TargetIdentifier?");
                } else {
                    $aggregateDefaultIdentifiers[$aggregateIdentifierName] = $mappingKey;
                }
            }
        }

        $this->messageIdentifierMapping = $aggregateDefaultIdentifiers;
        $this->expectedVersionPropertyName = $expectedVersionPropertyName;
    }

    /**
     * @param string $aggregateClassName
     * @param string $methodName
     *
     * @param string $handledMessageClassName
     * @return AggregateMessageHandlerBuilder
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createAggregateCommandHandlerWith(string $aggregateClassName, string $methodName, string $handledMessageClassName): self
    {
        return new self($aggregateClassName, $methodName, true, $handledMessageClassName);
    }

    /**
     * @param string $aggregateClassName
     * @param string $methodName
     *
     * @param string $handledMessageClassName
     * @return AggregateMessageHandlerBuilder
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createAggregateQueryHandlerWith(string $aggregateClassName, string $methodName, string $handledMessageClassName): self
    {
        return new self($aggregateClassName, $methodName, false, $handledMessageClassName);
    }

    /**
     * @inheritDoc
     */
    public function getParameterConverters(): array
    {
        return $this->methodParameterConverterBuilders;
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
    public function resolveRelatedReference(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [
            $interfaceToCallRegistry->getFor($this->aggregateClassName, $this->methodName)
        ];
    }

    /**
     * @param array $aggregateRepositoryFactories
     * @return AggregateMessageHandlerBuilder
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function withAggregateRepositoryFactories(array $aggregateRepositoryFactories): self
    {
        Assert::allInstanceOfType($aggregateRepositoryFactories, AggregateRepositoryFactory::class);
        $this->aggregateRepositoryFactories = $aggregateRepositoryFactories;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function registerRequiredReference(string $referenceName): void
    {
        $this->requiredReferences[] = $referenceName;
    }

    /**
     * @inheritDoc
     */
    public function withMethodParameterConverters(array $methodParameterConverterBuilders): self
    {
        Assert::allInstanceOfType($methodParameterConverterBuilders, ParameterConverterBuilder::class);

        $this->methodParameterConverterBuilders = $methodParameterConverterBuilders;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        $propertyReader = new PropertyReaderAccessor();
        $chainCqrsMessageHandler = ChainMessageHandlerBuilder::create();
        $aggregateRepository = null;

        foreach ($this->aggregateRepositoryFactories as $aggregateRepositoryFactory) {
            if ($aggregateRepositoryFactory->canHandle($referenceSearchService, $this->aggregateClassName)) {
                $aggregateRepository = $aggregateRepositoryFactory->getFor($referenceSearchService, $this->aggregateClassName);
            }
        }
        Assert::notNull($aggregateRepository, "Aggregate Repository not found for {$this->aggregateClassName}:{$this->methodName}");

        $chainCqrsMessageHandler
            ->chain(
                ServiceActivatorBuilder::createWithDirectReference(
                    new LoadAggregateService(
                        $aggregateRepository,
                        $this->aggregateClassName,
                        $this->methodName,
                        $this->isFactoryMethod,
                        $this->messageIdentifierMapping,
                        $this->expectedVersionPropertyName,
                        $propertyReader
                    ),
                    "load"
                )
            );

        $methodParameterConverters = [];
        foreach ($this->methodParameterConverterBuilders as $parameterConverterBuilder) {
            $methodParameterConverters[] = $parameterConverterBuilder->build($referenceSearchService);
        }

        $chainCqrsMessageHandler
            ->chain(
                ServiceActivatorBuilder::createWithDirectReference(
                    new CallAggregateService($channelResolver, $methodParameterConverters, AroundInterceptorReference::createAroundInterceptors($referenceSearchService, $this->orderedAroundInterceptors), $referenceSearchService),
                    "call"
                )->withPassThroughMessageOnVoidInterface($this->isVoidMethod)
            );

        if ($this->isCommandHandler) {
            $chainCqrsMessageHandler
                ->chain(
                    ServiceActivatorBuilder::createWithDirectReference(
                        new SaveAggregateService($aggregateRepository, $propertyReader),
                        "save"
                    )
                );
        }

        return $chainCqrsMessageHandler
            ->withOutputMessageChannel($this->outputMessageChannelName)
            ->build($channelResolver, $referenceSearchService);
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor($this->aggregateClassName, $this->methodName);
    }

    public function __toString()
    {
        return sprintf("Aggregate Handler - %s:%s with name `%s` for input channel `%s`", $this->aggregateClassName, $this->methodName, $this->getEndpointId(), $this->getInputMessageChannelName());
    }
}