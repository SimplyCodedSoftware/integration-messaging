<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Conversion\UuidToString;
use Ramsey\Uuid\UuidInterface;
use SimplyCodedSoftware\Messaging\Conversion\Converter;
use SimplyCodedSoftware\Messaging\Conversion\MediaType;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class UuidToStringConverter
 * @package SimplyCodedSoftware\Messaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class UuidToStringConverter implements Converter
{
    /**
     * @inheritDoc
     */
    public function convert($source, TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType)
    {
        /** @var UuidInterface $source */
        Assert::isSubclassOf($source, UuidInterface::class, "Passed type to String to Uuid converter is not Uuid");

        return $source->toString();
    }

    /**
     * @inheritDoc
     */
    public function matches(TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType): bool
    {
        return ($sourceType->isClassOfType(UuidInterface::class) && $targetType->isString());
    }
}