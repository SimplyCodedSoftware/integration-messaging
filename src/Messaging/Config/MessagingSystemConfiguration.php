<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Config;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\Messaging\Channel\ChannelInterceptorBuilder;
use SimplyCodedSoftware\Messaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Conversion\AutoCollectionConversionService;
use SimplyCodedSoftware\Messaging\Conversion\ConversionService;
use SimplyCodedSoftware\Messaging\Conversion\ConverterBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\ChannelAdapterConsumerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerEndpointFactory;
use SimplyCodedSoftware\Messaging\Endpoint\MessageHandlerConsumerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\PollableChannel;
use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class Configuration
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class MessagingSystemConfiguration implements Configuration
{
    /**
     * @var MessageChannelBuilder[]
     */
    private $channelBuilders = [];
    /**
     * @var ChannelInterceptorBuilder[]
     */
    private $channelInterceptorBuilders = [];
    /**
     * @var MessageHandlerBuilder[]
     */
    private $messageHandlerBuilders = [];
    /**
     * @var PollingMetadata[]
     */
    private $messageHandlerPollingMetadata = [];
    /**
     * @var Module[]
     */
    private $modules = [];
    /**
     * @var array|GatewayBuilder[]
     */
    private $gatewayBuilders = [];
    /**
     * @var MessageHandlerConsumerBuilder[]
     */
    private $consumerFactories = [];
    /**
     * @var ChannelAdapterConsumerBuilder[]
     */
    private $channelAdapters = [];
    /**
     * @var MessageHandlerBuilderWithOutputChannel[]
     */
    private $preCallMethodInterceptors = [];
    /**
     * @var MessageHandlerBuilderWithOutputChannel[]
     */
    private $postCallMethodInterceptors = [];
    /**
     * @var string[]
     */
    private $requiredReferences = [];
    /**
     * @var string[]
     */
    private $registeredGateways = [];
    /**
     * @var ConverterBuilder[]
     */
    private $converterBuilders = [];
    /**
     * @var object[]
     */
    private $extensionReferenceObjects;

    /**
     * Only one instance at time
     *
     * Configuration constructor.
     * @param ModuleRetrievingService $moduleConfigurationRetrievingService
     * @param object[] $extensionObjects
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function __construct(ModuleRetrievingService $moduleConfigurationRetrievingService, array $extensionObjects)
    {
        $this->initialize($moduleConfigurationRetrievingService, $extensionObjects);
    }

    /**
     * @param ModuleRetrievingService $moduleConfigurationRetrievingService
     * @param object[] $extensionObjects
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function initialize(ModuleRetrievingService $moduleConfigurationRetrievingService, array $extensionObjects): void
    {
        $configurableReferenceSearchService = ConfigurableReferenceSearchService::createEmpty();
        $modules = $moduleConfigurationRetrievingService->findAllModuleConfigurations();
        $moduleExtensions = [];

        foreach ($modules as $module) {
            $this->requireReferences($module->getRequiredReferences());

            $moduleExtensions[$module->getName()] = [];
            foreach ($extensionObjects as $extensionObject) {
                if ($module->canHandle($extensionObject)) {
                    $moduleExtensions[$module->getName()][] = $extensionObject;
                }
            }

            $this->modules[] = $module;
        }

        foreach ($this->modules as $module) {
            $module->prepare(
                $this,
                $moduleExtensions[$module->getName()],
                $configurableReferenceSearchService
            );
        }

        $this->extensionReferenceObjects = $configurableReferenceSearchService->getReferenceObjects();
    }

    /**
     * @param ModuleRetrievingService $moduleConfigurationRetrievingService
     * @return Configuration
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function prepare(ModuleRetrievingService $moduleConfigurationRetrievingService): Configuration
    {
        return new self($moduleConfigurationRetrievingService, $moduleConfigurationRetrievingService->findAllExtensionObjects());
    }

    /**
     * @param ModuleRetrievingService $moduleRetrievingService
     * @param array $extensionObjects
     * @return Configuration
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function prepareWithExtensions(ModuleRetrievingService $moduleRetrievingService, array $extensionObjects) : Configuration
    {
        return new self($moduleRetrievingService, array_merge($moduleRetrievingService->findAllExtensionObjects(), $extensionObjects));
    }

    /**
     * @param PollingMetadata $pollingMetadata
     * @return Configuration
     */
    public function registerPollingMetadata(PollingMetadata $pollingMetadata): Configuration
    {
        $this->messageHandlerPollingMetadata[$pollingMetadata->getEndpointId()] = $pollingMetadata;

        return $this;
    }

    /**
     * @param OrderedMethodInterceptor $methodInterceptor
     * @return Configuration
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function registerPreCallMethodInterceptor(OrderedMethodInterceptor $methodInterceptor): Configuration
    {
        $this->checkIfInterceptorIsCorrect($methodInterceptor);

        $this->preCallMethodInterceptors[] = $methodInterceptor;
        $this->preCallMethodInterceptors = $this->orderMethodInterceptors($this->preCallMethodInterceptors);

        return $this;
    }

    /**
     * @param OrderedMethodInterceptor $methodInterceptor
     * @return Configuration
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function registerPostCallMethodInterceptor(OrderedMethodInterceptor $methodInterceptor): Configuration
    {
        $this->checkIfInterceptorIsCorrect($methodInterceptor);

        $this->postCallMethodInterceptors[] = $methodInterceptor;
        $this->postCallMethodInterceptors = $this->orderMethodInterceptors($this->postCallMethodInterceptors);

        return $this;
    }

    /**
     * @param MessageHandlerBuilderWithOutputChannel[] $methodInterceptors
     * @return array
     */
    private function orderMethodInterceptors(array $methodInterceptors) : array
    {
        usort($methodInterceptors, function(OrderedMethodInterceptor $methodInterceptor, OrderedMethodInterceptor $toCompare){
            if ($methodInterceptor->getOrderWeight() === $toCompare->getOrderWeight()) {
                return 0;
            }

            if ($methodInterceptor->getOrderWeight() > $toCompare->getOrderWeight()) {
                return -1;
            }

            return 1;
        });

        return $methodInterceptors;
    }

    /**
     * @inheritDoc
     */
    public function registerChannelInterceptor(ChannelInterceptorBuilder $channelInterceptorBuilder): Configuration
    {
        $this->channelInterceptorBuilders[$channelInterceptorBuilder->getImportanceOrder()][] = $channelInterceptorBuilder;
        $this->requireReferences($channelInterceptorBuilder->getRequiredReferenceNames());

        return $this;
    }

    /**
     * @param string[] $referenceNames
     */
    private function requireReferences(array $referenceNames): void
    {
        foreach ($referenceNames as $requiredReferenceName) {
            if ($requiredReferenceName) {
                $this->requiredReferences[] = $requiredReferenceName;
            }
        }
    }

    /**
     * @param MessageHandlerBuilder $messageHandlerBuilder
     * @return Configuration
     * @throws ConfigurationException
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function registerMessageHandler(MessageHandlerBuilder $messageHandlerBuilder): Configuration
    {
        Assert::notNullAndEmpty($messageHandlerBuilder->getInputMessageChannelName(), "Lack information about input message channel for {$messageHandlerBuilder}");

        if (is_null($messageHandlerBuilder->getEndpointId()) || $messageHandlerBuilder->getEndpointId() === "") {
            $messageHandlerBuilder->withEndpointId(Uuid::uuid4()->toString());
        }
        if (array_key_exists($messageHandlerBuilder->getEndpointId(), $this->messageHandlerBuilders)) {
            throw ConfigurationException::create("Trying to register endpoints with same id. {$messageHandlerBuilder} and {$this->messageHandlerBuilders[$messageHandlerBuilder->getEndpointId()]}");
        }

        $this->requireReferences($messageHandlerBuilder->getRequiredReferenceNames());

        if ($messageHandlerBuilder instanceof MessageHandlerBuilderWithParameterConverters) {
            foreach ($messageHandlerBuilder->getParameterConverters() as $parameterConverter) {
                $this->requireReferences($parameterConverter->getRequiredReferences());
            }
        }

        $this->messageHandlerBuilders[$messageHandlerBuilder->getEndpointId()] = $messageHandlerBuilder;

        return $this;
    }

    /**
     * @param MessageChannelBuilder $messageChannelBuilder
     * @return Configuration
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function registerMessageChannel(MessageChannelBuilder $messageChannelBuilder): Configuration
    {
        if (array_key_exists($messageChannelBuilder->getMessageChannelName(), $this->channelBuilders)) {
            throw ConfigurationException::create("Trying to register message channel with name `{$messageChannelBuilder->getMessageChannelName()}` twice.");
        }

        $this->channelBuilders[$messageChannelBuilder->getMessageChannelName()] = $messageChannelBuilder;
        $this->requireReferences($messageChannelBuilder->getRequiredReferenceNames());

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function registerConsumer(ChannelAdapterConsumerBuilder $consumerBuilder): Configuration
    {
        $this->channelAdapters[] = $consumerBuilder;
        $this->requireReferences($consumerBuilder->getRequiredReferences());

        return $this;
    }

    /**
     * @param GatewayBuilder $gatewayBuilder
     * @return Configuration
     */
    public function registerGatewayBuilder(GatewayBuilder $gatewayBuilder): Configuration
    {
        $this->gatewayBuilders[] = $gatewayBuilder;
        $this->registeredGateways[$gatewayBuilder->getReferenceName()] = $gatewayBuilder->getInterfaceName();
        $this->requireReferences($gatewayBuilder->getRequiredReferences());

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function registerConsumerFactory(MessageHandlerConsumerBuilder $consumerFactory): Configuration
    {
        $this->consumerFactories[] = $consumerFactory;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getRequiredReferences() : array
    {
        return $this->requiredReferences;
    }

    /**
     * @return string[]
     */
    public function getRegisteredGateways() : array
    {
        return $this->registeredGateways;
    }

    /**
     * @inheritDoc
     */
    public function registerConverter(ConverterBuilder $converterBuilder): Configuration
    {
        $this->converterBuilders[] = $converterBuilder;

        return $this;
    }

    /**
     * Initialize messaging system from current configuration
     *
     * @param ReferenceSearchService $externalReferenceSearchService
     * @return ConfiguredMessagingSystem
     * @throws \SimplyCodedSoftware\Messaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function buildMessagingSystemFromConfiguration(ReferenceSearchService $externalReferenceSearchService): ConfiguredMessagingSystem
    {
        foreach ($this->messageHandlerBuilders as $messageHandlerBuilder) {
            if (!array_key_exists($messageHandlerBuilder->getInputMessageChannelName(), $this->channelBuilders)) {
                $this->channelBuilders[$messageHandlerBuilder->getInputMessageChannelName()] = SimpleMessageChannelBuilder::createDirectMessageChannel($messageHandlerBuilder->getInputMessageChannelName());
            }
        }

        foreach ($this->modules as $module) {
            $module->afterConfigure($externalReferenceSearchService);
        }

        $converters = [];
        foreach ($this->converterBuilders as $converterBuilder) {
            $converters[] = $converterBuilder->build($externalReferenceSearchService);
        }
        $extraReferences[ConversionService::REFERENCE_NAME] = AutoCollectionConversionService::createWith($converters);

        $referenceSearchService = InMemoryReferenceSearchService::createWithReferenceService($externalReferenceSearchService, array_merge($this->extensionReferenceObjects, $extraReferences));
        $channelResolver = $this->createChannelResolver($referenceSearchService);
        $gateways = [];
        foreach ($this->gatewayBuilders as $gatewayBuilder) {
            $gatewayReference = GatewayReference::createWith($gatewayBuilder, $referenceSearchService, $channelResolver);
            $gateways[] = $gatewayReference;
        }

        $preCallInterceptors = array_map(function (OrderedMethodInterceptor $methodInterceptor){
            return $methodInterceptor->getMessageHandler();
        }, $this->preCallMethodInterceptors);
        $postCallInterceptors = array_map(function (OrderedMethodInterceptor $methodInterceptor) {
            return $methodInterceptor->getMessageHandler();
        }, $this->postCallMethodInterceptors);
        $consumerEndpointFactory = new ConsumerEndpointFactory($channelResolver, $referenceSearchService, $this->consumerFactories, $preCallInterceptors, $postCallInterceptors, $this->messageHandlerPollingMetadata);
        $consumers = [];

        foreach ($this->messageHandlerBuilders as $messageHandlerBuilder) {
            $consumers[] = $consumerEndpointFactory->createForMessageHandler($messageHandlerBuilder);
        }
        foreach ($this->channelAdapters as $channelAdapter) {
            $consumers[] = $channelAdapter->build($channelResolver, $referenceSearchService);
        }

        $messagingSystem = MessagingSystem::create($consumers, $gateways, $channelResolver);

        return $messagingSystem;
    }

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @return ChannelResolver
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function createChannelResolver(ReferenceSearchService $referenceSearchService): ChannelResolver
    {
        $channelInterceptorsByImportance = $this->channelInterceptorBuilders;
        arsort($channelInterceptorsByImportance);
        $channelInterceptorsByChannelName = [];

        foreach ($channelInterceptorsByImportance as $channelInterceptors) {
            foreach ($channelInterceptors as $channelInterceptor) {
                $channelInterceptorsByChannelName[$channelInterceptor->relatedChannelName()][] = $channelInterceptor->build($referenceSearchService);
            }
        }

        $channels = [];
        foreach ($this->channelBuilders as $channelsBuilder) {
            $messageChannel = $channelsBuilder->build($referenceSearchService);
            $interceptorsForChannel = [];
            foreach ($channelInterceptorsByChannelName as $channelName => $interceptors) {
                $regexChannel = str_replace("*", ".*", $channelName);
                if (preg_match("#^{$regexChannel}$#", $channelsBuilder->getMessageChannelName())) {
                    $interceptorsForChannel = array_merge($interceptorsForChannel, $interceptors);
                }
            }

            if ($messageChannel instanceof PollableChannel) {
                $messageChannel = new PollableChannelInterceptorAdapter($messageChannel, $interceptorsForChannel);
            } else {
                $messageChannel = new EventDrivenChannelInterceptorAdapter($messageChannel, $interceptorsForChannel);
            }

            $channels[] = NamedMessageChannel::create($channelsBuilder->getMessageChannelName(), $messageChannel);
        }

        return InMemoryChannelResolver::create($channels);
    }

    /**
     * Only one instance at time
     *
     * @internal
     */
    private function __clone()
    {

    }

    /**
     * @param OrderedMethodInterceptor $methodInterceptor
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function checkIfInterceptorIsCorrect(OrderedMethodInterceptor $methodInterceptor): void
    {
        if (!$methodInterceptor->getMessageHandler()->getEndpointId()) {
            throw ConfigurationException::create("Interceptor {$methodInterceptor} lack of endpoint id");
        }
        if ($methodInterceptor->getMessageHandler()->getInputMessageChannelName()) {
            throw ConfigurationException::create("Interceptor {$methodInterceptor} should not contain input channel. Interceptor is wired by endpoint id");
        }
        if ($methodInterceptor->getMessageHandler()->getOutputMessageChannelName()) {
            throw ConfigurationException::create("Interceptor {$methodInterceptor} should not contain output channel. Interceptor is wired by endpoint id");
        }
    }
}