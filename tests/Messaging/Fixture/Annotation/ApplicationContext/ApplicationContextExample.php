<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\ApplicationContext;

use SimplyCodedSoftware\Messaging\Annotation\ApplicationContext;
use SimplyCodedSoftware\Messaging\Annotation\Extension;
use SimplyCodedSoftware\Messaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Channel\SimpleChannelInterceptorBuilder;
use SimplyCodedSoftware\Messaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\Transformer\TransformerBuilder;

/**
 * Class ApplicationContext
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\ApplicationContext
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ApplicationContext()
 */
class ApplicationContextExample
{
    const HTTP_INPUT_CHANNEL = "httpEntry";
    const HTTP_OUTPUT_CHANNEL = "httpOutput";

    /**
     * @return GatewayBuilder
     * @Extension()
     */
    public function gateway(): GatewayBuilder
    {
        return GatewayProxyBuilder::create("some-ref", GatewayExample::class, "doSomething", self::HTTP_INPUT_CHANNEL);
    }

    /**
     * @return MessageChannelBuilder
     * @Extension()
     */
    public function httpEntryChannel(): MessageChannelBuilder
    {
        return SimpleMessageChannelBuilder::createDirectMessageChannel(self::HTTP_INPUT_CHANNEL);
    }

    /**
     * @return MessageHandlerBuilder
     * @Extension()
     */
    public function enricherHttpEntry(): MessageHandlerBuilder
    {
        return TransformerBuilder::createHeaderEnricher([
            "token" => "abcedfg"
        ])
            ->withInputChannelName(self::HTTP_INPUT_CHANNEL)
            ->withOutputMessageChannel(self::HTTP_OUTPUT_CHANNEL)
            ->withEndpointId("some-id");
    }

    /**
     * @Extension()
     */
    public function withChannelInterceptors()
    {
        return SimpleChannelInterceptorBuilder::create(self::HTTP_INPUT_CHANNEL, "ref");
    }

    /**
     * @return \stdClass
     * @Extension()
     */
    public function withStdClassConverterByExtension(): \stdClass
    {
        return new \stdClass();
    }

    /**
     * @return \stdClass
     */
    public function wrongExtensionObject(): \stdClass
    {
        return new \stdClass();
    }
}