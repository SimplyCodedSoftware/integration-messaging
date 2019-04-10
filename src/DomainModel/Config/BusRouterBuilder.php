<?php

namespace SimplyCodedSoftware\DomainModel\Config;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\DomainModel\CommandBus;
use SimplyCodedSoftware\DomainModel\EventBus;
use SimplyCodedSoftware\DomainModel\QueryBus;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\HeaderBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\Router\RouterBuilder;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Class BusRouterBuilder
 * @package SimplyCodedSoftware\DomainModel\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class BusRouterBuilder implements MessageHandlerBuilder
{
    /**
     * @var string
     */
    private $endpointId;

    /**
     * @var array
     */
    private $channelNamesRouting;
    /**
     * @var string[]
     */
    private $inputChannelName;
    /**
     * @var string
     */
    private $type;

    /**
     * BusRouterBuilder constructor.
     *
     * @param string $endpointId
     * @param string $inputChannelName
     * @param string[]  $channelNamesRouting
     * @param string $type
     *
     * @throws \Exception
     */
    private function __construct(string $endpointId, string $inputChannelName, array $channelNamesRouting, string $type)
    {
        $this->channelNamesRouting = $channelNamesRouting;
        $this->inputChannelName = $inputChannelName;
        $this->type = $type;
        $this->endpointId = $endpointId;
    }

    /**
     * @param string[] $channelNamesRouting
     *
     * @return BusRouterBuilder
     * @throws \Exception
     */
    public static function createEventBusByObject(array $channelNamesRouting) : self
    {
        return new self(
            EventBus::CHANNEL_NAME_BY_OBJECT,
            EventBus::CHANNEL_NAME_BY_OBJECT,
            $channelNamesRouting,
            "eventByObject"
        );
    }

    /**
     * @param string[] $channelNamesRouting
     *
     * @return BusRouterBuilder
     * @throws \Exception
     */
    public static function createEventBusByName(array $channelNamesRouting) : self
    {
        return new self(
            EventBus::CHANNEL_NAME_BY_NAME,
            EventBus::CHANNEL_NAME_BY_NAME,
            $channelNamesRouting,
            "eventByName"
        );
    }

    /**
     * @param string[] $channelNamesRouting
     *
     * @return BusRouterBuilder
     * @throws \Exception
     */
    public static function createCommandBusByObject(array $channelNamesRouting) : self
    {
        return new self(
            CommandBus::CHANNEL_NAME_BY_OBJECT,
            CommandBus::CHANNEL_NAME_BY_OBJECT,
            $channelNamesRouting,
            "commandByObject"
        );
    }

    /**
     * @param string[] $channelNamesRouting
     *
     * @return BusRouterBuilder
     * @throws \Exception
     */
    public static function createCommandBusByName(array $channelNamesRouting) : self
    {
        return new self(
            CommandBus::CHANNEL_NAME_BY_NAME,
            CommandBus::CHANNEL_NAME_BY_NAME,
            $channelNamesRouting,
            "commandByName"
        );
    }

    /**
     * @param string[] $channelNamesRouting
     *
     * @return BusRouterBuilder
     * @throws \Exception
     */
    public static function createQueryBusByObject(array $channelNamesRouting) : self
    {
        return new self(
            QueryBus::CHANNEL_NAME_BY_OBJECT,
            QueryBus::CHANNEL_NAME_BY_OBJECT,
            $channelNamesRouting,
            "queryByObject"
        );
    }

    /**
     * @param string[] $channelNamesRouting
     *
     * @return BusRouterBuilder
     * @throws \Exception
     */
    public static function createQueryBusByName(array $channelNamesRouting) : self
    {
        return new self(
            QueryBus::CHANNEL_NAME_BY_NAME,
            QueryBus::CHANNEL_NAME_BY_NAME,
            $channelNamesRouting,
            "queryByName"
        );
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        switch ($this->type) {
            case "eventByObject": {
                return RouterBuilder::createRouterFromObject(
                    new EventBusRouter($this->channelNamesRouting, $channelResolver),
                    "routeByObject"
                )   ->setResolutionRequired(false)
                    ->build($channelResolver, $referenceSearchService);
            }
            case "eventByName": {
                return RouterBuilder::createRouterFromObject(
                    new EventBusRouter($this->channelNamesRouting, $channelResolver),
                    "routeByName"
                )
                    ->setResolutionRequired(false)
                    ->withMethodParameterConverters([
                        HeaderBuilder::createOptional("name", EventBus::CHANNEL_NAME_BY_NAME)
                    ])
                    ->build($channelResolver, $referenceSearchService);
            }
            case "commandByObject": {
                return RouterBuilder::createRouterFromObject(
                    new CommandBusRouter($this->channelNamesRouting, $channelResolver),
                    "routeByObject"
                )->build($channelResolver, $referenceSearchService);
            }
            case "commandByName": {
                return RouterBuilder::createRouterFromObject(
                    new CommandBusRouter($this->channelNamesRouting, $channelResolver),
                    "routeByName"
                )
                    ->withMethodParameterConverters([
                        HeaderBuilder::createOptional("name", CommandBus::CHANNEL_NAME_BY_NAME)
                    ])
                    ->build($channelResolver, $referenceSearchService);
            }
            case "queryByObject": {
                return RouterBuilder::createRouterFromObject(
                    new QueryBusRouter($this->channelNamesRouting, $channelResolver),
                    "routeByObject"
                )->build($channelResolver, $referenceSearchService);
            }
            case "queryByName": {
                return RouterBuilder::createRouterFromObject(
                    new QueryBusRouter($this->channelNamesRouting, $channelResolver),
                    "routeByName"
                )
                    ->withMethodParameterConverters([
                        HeaderBuilder::createOptional("name", QueryBus::CHANNEL_NAME_BY_NAME)
                    ])
                    ->build($channelResolver, $referenceSearchService);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedReferences(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function withInputChannelName(string $inputChannelName)
    {
        $this->inputChannelName = $inputChannelName;
    }

    /**
     * @inheritDoc
     */
    public function getEndpointId(): ?string
    {
        return $this->endpointId;
    }

    /**
     * @inheritDoc
     */
    public function withEndpointId(string $endpointId)
    {
        $this->endpointId = $endpointId;
    }

    /**
     * @inheritDoc
     */
    public function getInputMessageChannelName(): string
    {
        return $this->inputChannelName;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [];
    }

    public function __toString()
    {
        return BusRouterBuilder::class;
    }
}