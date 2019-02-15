<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\MessageConverter;

use SimplyCodedSoftware\Messaging\Conversion\ConversionService;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class GenericMessageConverter
 * @package SimplyCodedSoftware\Messaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * An extension of the MessageConverter that uses a ConversionService to convert the payload of the message to the requested type.
 * Return null if the conversion service cannot convert from the payload type to the requested type.
 */
class GenericMessageConverter implements MessageConverter
{
    /**
     * @var ConversionService
     */
    private $conversionService;

    /**
     * GenericMessageConverter constructor.
     * @param ConversionService $conversionService
     */
    public function __construct(ConversionService $conversionService)
    {
        $this->conversionService = $conversionService;
    }

    /**
     * @inheritDoc
     */
    public function fromMessage(Message $message, TypeDescriptor $targetType)
    {

    }

    /**
     * @inheritDoc
     */
    public function toMessage($source, array $messageHeaders): ?MessageBuilder
    {
        if ($messageHeaders[MessageHeaders::CONTENT_TYPE]) {

        }
    }
}