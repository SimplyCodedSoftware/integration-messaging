<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined;

use SimplyCodedSoftware\Messaging\Annotation\EndpointId;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Header;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\MessageParameter;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Reference;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Value;
use SimplyCodedSoftware\Messaging\Annotation\ServiceActivator;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Class ServiceActivatorWithAllConfigurationDefined
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class ServiceActivatorWithAllConfigurationDefined
{
    /**
     * @param string    $to
     * @param string    $content
     * @param Message   $message
     * @param \stdClass $object
     * @param string    $name
     *
     * @return void
     * @ServiceActivator(endpointId="test-name", inputChannelName="inputChannel", outputChannelName="outputChannel", requiresReply=true, parameterConverters={
     *     @Header(parameterName="to", headerName="sendTo"),
     *     @Payload(parameterName="content"),
     *     @Payload(parameterName="content2", expression="value"),
     *     @MessageParameter(parameterName="message"),
     *     @Reference(parameterName="object", referenceName="reference"),
     *     @Header(parameterName="name", headerName="token", expression="value", isRequired=false)
     * }, requiredInterceptorNames={"someReference"})
     */
    public function sendMessage(string $to, string $content, string $content2, Message $message, \stdClass $object, string $name) : void
    {
        return;
    }
}