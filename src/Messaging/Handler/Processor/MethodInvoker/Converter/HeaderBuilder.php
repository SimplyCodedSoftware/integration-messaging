<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter;

use SimplyCodedSoftware\Messaging\Handler\ParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class HeaderBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class HeaderBuilder implements ParameterConverterBuilder
{
    /**
     * @var string
     */
    private $headerName;
    /**
     * @var string
     */
    private $parameterName;
    /**
     * @var bool
     */
    private $isRequired;

    /**
     * HeaderArgument constructor.
     *
     * @param string $parameterName
     * @param string $headerName
     * @param bool   $isRequired
     */
    private function __construct(string $parameterName, string $headerName, bool $isRequired)
    {
        $this->parameterName = $parameterName;
        $this->headerName = $headerName;
        $this->isRequired = $isRequired;
    }

    /**
     * @param string $parameterName
     * @param string $headerName
     *
     * @return HeaderBuilder
     */
    public static function create(string $parameterName, string $headerName) : self
    {
        return new self($parameterName, $headerName, true);
    }

    /**
     * @param string $parameterName
     * @param string $headerName
     * @return HeaderBuilder
     */
    public static function createOptional(string $parameterName, string $headerName) : self
    {
        return new self($parameterName, $headerName, false);
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): ParameterConverter
    {
        return HeaderConverter::create(
            $this->parameterName,
            $this->headerName,
            $this->isRequired
        );
    }
}