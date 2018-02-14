<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\MethodArgument;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class PayloadMessageParameter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\Gateway\MethodParameterConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ParameterToPayloadConverter implements ParameterToMessageConverter
{
    /**
     * @var string
     */
    private $parameterName;

    /**
     * PayloadMessageParameter constructor.
     * @param string $parameterName
     */
    private function __construct(string $parameterName)
    {
        $this->parameterName = $parameterName;
    }

    /**
     * @param string $parameterName
     * @return ParameterToPayloadConverter
     */
    public static function create(string $parameterName) : self
    {
        return new self($parameterName);
    }

    /**
     * @inheritDoc
     */
    public function isSupporting(MethodArgument $methodArgument): bool
    {
        return $this->parameterName == $methodArgument->getParameterName();
    }

    /**
     * @inheritDoc
     */
    public function convertToMessage(MethodArgument $methodArgument, MessageBuilder $messageBuilder): MessageBuilder
    {
        return $messageBuilder->setPayload($methodArgument->value());
    }
}