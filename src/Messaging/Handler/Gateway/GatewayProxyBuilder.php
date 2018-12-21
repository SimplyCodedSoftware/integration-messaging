<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;

use ProxyManager\Factory\RemoteObject\AdapterInterface;
use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\PollableChannel;
use SimplyCodedSoftware\Messaging\SubscribableChannel;
use SimplyCodedSoftware\Messaging\Support\Assert;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class GatewayProxySpec
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayProxyBuilder implements GatewayBuilder
{
    const DEFAULT_REPLY_MILLISECONDS_TIMEOUT = -1;

    /**
     * @var string
     */
    private $referenceName;
    /**
     * @var string
     */
    private $interfaceName;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var string
     */
    private $requestChannelName;
    /**
     * @var int
     */
    private $replyMilliSecondsTimeout = self::DEFAULT_REPLY_MILLISECONDS_TIMEOUT;
    /**
     * @var string
     */
    private $replyChannelName;
    /**
     * @var array|GatewayParameterConverterBuilder[]
     */
    private $methodArgumentConverters = [];
    /**
     * @var CustomSendAndReceiveService
     */
    private $customSendAndReceiveService;
    /**
     * @var string
     */
    private $errorChannelName;
    /**
     * @var string[]
     */
    private $transactionFactoryReferenceNames = [];
    /**
     * @var string[]
     */
    private $requiredReferenceNames = [];

    /**
     * GatewayProxyBuilder constructor.
     * @param string $referenceName
     * @param string $interfaceName
     * @param string $methodName
     * @param string $requestChannelName
     */
    private function __construct(string $referenceName, string $interfaceName, string $methodName, string $requestChannelName)
    {
        $this->referenceName = $referenceName;
        $this->interfaceName = $interfaceName;
        $this->methodName = $methodName;
        $this->requestChannelName = $requestChannelName;
    }

    /**
     * @param string $referenceName
     * @param string $interfaceName
     * @param string $methodName
     * @param string $requestChannelName
     * @return GatewayProxyBuilder
     */
    public static function create(string $referenceName, string $interfaceName, string $methodName, string $requestChannelName): self
    {
        return new self($referenceName, $interfaceName, $methodName, $requestChannelName);
    }

    /**
     * @param string $replyChannelName where to expect reply
     * @return GatewayProxyBuilder
     */
    public function withReplyChannel(string $replyChannelName): self
    {
        $this->replyChannelName = $replyChannelName;

        return $this;
    }

    /**
     * @param string $errorChannelName
     * @return GatewayProxyBuilder
     */
    public function withErrorChannel(string $errorChannelName) : self
    {
        $this->errorChannelName = $errorChannelName;

        return $this;
    }

    /**
     * @param int $replyMillisecondsTimeout
     * @return GatewayProxyBuilder
     */
    public function withReplyMillisecondTimeout(int $replyMillisecondsTimeout): self
    {
        $this->replyMilliSecondsTimeout = $replyMillisecondsTimeout;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return $this->requiredReferenceNames;
    }

    /**
     * @inheritDoc
     */
    public function getReferenceName(): string
    {
        return $this->referenceName;
    }

    /**
     * @inheritDoc
     */
    public function getInterfaceName(): string
    {
        return $this->interfaceName;
    }

    /**
     * @param CustomSendAndReceiveService $sendAndReceiveService
     * @return GatewayProxyBuilder
     */
    public function withCustomSendAndReceiveService(CustomSendAndReceiveService $sendAndReceiveService) : self
    {
        $this->customSendAndReceiveService = $sendAndReceiveService;

        return $this;
    }

    /**
     * @param array $methodArgumentConverters
     * @return GatewayProxyBuilder
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function withParameterToMessageConverters(array $methodArgumentConverters): self
    {
        Assert::allInstanceOfType($methodArgumentConverters, GatewayParameterConverterBuilder::class);

        $this->methodArgumentConverters = $methodArgumentConverters;

        return $this;
    }

    /**
     * @param string[] $transactionFactoryReferenceNames
     * @return GatewayProxyBuilder
     */
    public function withTransactionFactories(array $transactionFactoryReferenceNames) : self
    {
        $this->transactionFactoryReferenceNames = $transactionFactoryReferenceNames;
        foreach ($transactionFactoryReferenceNames as $transactionFactoryReferenceName) {
            $this->requiredReferenceNames[] = $transactionFactoryReferenceName;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function build(ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver)
    {
        Assert::isInterface($this->interfaceName, "Gateway should point to interface instead of got {$this->interfaceName}");

        $replyChannel = $this->replyChannelName ? $channelResolver->resolve($this->replyChannelName) : null;
        $requestChannel = $channelResolver->resolve($this->requestChannelName);
        $interfaceToCall = InterfaceToCall::create($this->interfaceName, $this->methodName);

        if (!$interfaceToCall->hasReturnTypeVoid()) {
            /** @var DirectChannel $requestChannel */
            Assert::isSubclassOf($requestChannel, SubscribableChannel::class, "Gateway request channel should not be pollable if expecting reply");
        }

        if ($replyChannel) {
            /** @var PollableChannel $replyChannel */
            Assert::isSubclassOf($replyChannel, PollableChannel::class, "Reply channel must be pollable");
        }
        $errorChannel = $this->errorChannelName ? $channelResolver->resolve($this->errorChannelName) : null;

        $replyReceiver = DefaultSendAndReceiveService::create($requestChannel, $replyChannel, $errorChannel);
        if ($this->customSendAndReceiveService) {
            $replyReceiver = $this->customSendAndReceiveService;
            $this->customSendAndReceiveService->setSendAndReceive($requestChannel, $replyChannel, $errorChannel);
        }
        if ($replyChannel) {
            $replyReceiver = new ChannelSendAndReceiveService($requestChannel, $replyChannel, $errorChannel);
        }
        if ($this->replyChannelName && $this->replyMilliSecondsTimeout > 0) {
            $replyReceiver = new TimeoutChannelSendAndReceiveService($requestChannel, $replyChannel, $errorChannel, $this->replyMilliSecondsTimeout);
        }

        if (!$interfaceToCall->hasReturnValue() && $this->replyChannelName) {
            throw InvalidArgumentException::create("Can't set reply channel for {$interfaceToCall}");
        }

        $methodArgumentConverters = [];
        foreach ($this->methodArgumentConverters as $messageConverterBuilder) {
            $methodArgumentConverters[] = $messageConverterBuilder->build($referenceSearchService);
        }

        $transactionFactories = [];
        foreach ($this->transactionFactoryReferenceNames as $referenceName) {
            $transactionFactories[] = $referenceSearchService->get($referenceName);
        }

        $gateway = new Gateway(
            $this->interfaceName, $this->methodName,
            new MethodCallToMessageConverter(
                $this->interfaceName, $this->methodName, $methodArgumentConverters
            ),
            ErrorSendAndReceiveService::create($replyReceiver, $errorChannel),
            $transactionFactories
        );

        $factory = new \ProxyManager\Factory\RemoteObjectFactory(new class ($gateway) implements AdapterInterface {
            /**
             * @var Gateway
             */
            private $gatewayProxy;

            /**
             *  constructor.
             *
             * @param Gateway $gatewayProxy
             */
            public function __construct(Gateway $gatewayProxy)
            {
                $this->gatewayProxy = $gatewayProxy;
            }

            /**
             * @inheritDoc
             */
            public function call(string $wrappedClass, string $method, array $params = [])
            {
                return $this->gatewayProxy->execute($params);
            }
        });

        return $factory->createProxy($this->interfaceName);
    }

    public function __toString()
    {
        return sprintf("Gateway - %s:%s with reference name `%s` for request channel `%s`", $this->interfaceName, $this->methodName, $this->referenceName, $this->requestChannelName);
    }
}