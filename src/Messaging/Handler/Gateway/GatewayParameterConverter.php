<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;
use SimplyCodedSoftware\Messaging\Handler\MethodArgument;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Interface ParameterDefinition
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface GatewayParameterConverter
{
    /**
     * @param MethodArgument $methodArgument
     * @param MessageBuilder $messageBuilder
     * @return MessageBuilder
     */
    public function convertToMessage(MethodArgument $methodArgument, MessageBuilder $messageBuilder) : MessageBuilder;

    /**
     * @param MethodArgument $methodArgument
     * @return bool
     */
    public function isSupporting(MethodArgument $methodArgument) : bool;
}