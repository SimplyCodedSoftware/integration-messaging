<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Conversion;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;

/**
 * Class CollectionConverter
 * @package SimplyCodedSoftware\Messaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CollectionConverter implements Converter
{
    /**
     * @var Converter
     */
    private $converterForSingleType;

    /**
     * CollectionConverter constructor.
     * @param Converter $converterForSingleType
     */
    private function __construct(Converter $converterForSingleType)
    {
        $this->converterForSingleType = $converterForSingleType;
    }

    /**
     * @param Converter $converterForSingleType
     * @return CollectionConverter
     */
    public static function createForConverter(Converter $converterForSingleType) : self
    {
        return new self($converterForSingleType);
    }

    /**
     * @inheritDoc
     */
    public function convert($source, TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType)
    {
        $collection = [];
        foreach ($source as $element) {
            $collection[] = $this->converterForSingleType->convert(
                $element,
                $sourceType,
                $sourceMediaType,
                $targetType,
                $targetMediaType
            );
        }

        return $collection;
    }

    /**
     * @inheritDoc
     */
    public function matches(TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType): bool
    {
        return $sourceType->isCollection() && $targetType->isCollection()
            && $this->converterForSingleType->matches(
                $sourceType->resolveGenericTypes()[0], $sourceMediaType, $targetType->resolveGenericTypes()[0], $targetMediaType
            );
    }
}