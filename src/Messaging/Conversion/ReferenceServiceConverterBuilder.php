<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Conversion;

use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class ReferenceConverterBuilder
 * @package SimplyCodedSoftware\Messaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReferenceServiceConverterBuilder implements ConverterBuilder
{
    /**
     * @var string
     */
    private $referenceName;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var TypeDescriptor
     */
    private $sourceType;
    /**
     * @var TypeDescriptor
     */
    private $targetType;

    /**
     * ReferenceConverter constructor.
     * @param string $referenceName
     * @param string $method
     * @param TypeDescriptor $sourceType
     * @param TypeDescriptor $targetType
     */
    private function __construct(string $referenceName, string $method, TypeDescriptor $sourceType, TypeDescriptor $targetType)
    {
        $this->referenceName = $referenceName;
        $this->methodName = $method;
        $this->sourceType = $sourceType;
        $this->targetType = $targetType;
    }

    /**
     * @param string $referenceName
     * @param string $method
     * @param TypeDescriptor $sourceType
     * @param TypeDescriptor $targetType
     * @return ReferenceServiceConverterBuilder
     */
    public static function create(string $referenceName, string $method, TypeDescriptor $sourceType, TypeDescriptor $targetType) : self
    {
        return new self($referenceName, $method, $sourceType, $targetType);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): Converter
    {
        $object = $referenceSearchService->get($this->referenceName);

        $interfaceToCall = $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME)->getFor($object, $this->methodName);

        if ($interfaceToCall->hasMoreThanOneParameter()) {
            throw InvalidArgumentException::create("Converter should have only single parameter: {$interfaceToCall}");
        }

        return ReferenceServiceConverter::create(
            $object,
            $this->methodName,
            $this->sourceType,
            $this->targetType
        );
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [$this->referenceName];
    }
}