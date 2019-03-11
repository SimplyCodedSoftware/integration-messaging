<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler;

use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Future;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class InterfaceToCall
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InterfaceToCall
{
    private const COLLECTION_TYPE_REGEX = "/[a-zA-Z0-9]*<([^<]*)>/";
    private const CODE_USE_STATEMENTS_REGEX = '/use[\s]*([^;]*)[\s]*;/';
    private const METHOD_DOC_BLOCK_PARAMETERS_REGEX = '~@param[\s]*([^\n\$\s]*)[\s]*\$([a-zA-Z0-9]*)~';
    private const METHOD_RETURN_TYPE_REGEX = '~@return[\s]*([^\n\$\s]*)~';
    private const SELF_TYPE_HINT = "self";
    private const STATIC_TYPE_HINT = "static";
    private const THIS_TYPE_HINT = '$this';

    /**
     * @var string
     */
    private $interfaceName;
    /**
     * @var TypeDescriptor
     */
    private $interfaceType;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var array|InterfaceParameter[]
     */
    private $parameters;
    /**
     * @var TypeDescriptor
     */
    private $returnType;
    /**
     * @var bool
     */
    private $doesReturnTypeAllowNulls;
    /**
     * @var bool
     */
    private $isStaticallyCalled;
    /**
     * @var iterable|object[]
     */
    private $classAnnotations;
    /**
     * @var iterable|object[]
     */
    private $methodAnnotations;

    /**
     * InterfaceToCall constructor.
     * @param string $interfaceName
     * @param string $methodName
     * @param object[] $classAnnotations
     * @param object[] $methodAnnotations
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function __construct(string $interfaceName, string $methodName, iterable $classAnnotations = [], iterable $methodAnnotations = [])
    {
        $this->initialize($interfaceName, $methodName);
        $this->classAnnotations = $classAnnotations;
        $this->methodAnnotations = $methodAnnotations;
    }

    /**
     * @param string $interfaceName
     * @param string $methodName
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function initialize(string $interfaceName, string $methodName): void
    {
        $this->interfaceType = TypeDescriptor::create($interfaceName);
        $this->interfaceName = $this->interfaceType->toString();
        $this->methodName = $methodName;
        $reflectionClass = new \ReflectionClass($interfaceName);
        if (!$reflectionClass->hasMethod($methodName)) {
            throw InvalidArgumentException::create("Interface {$interfaceName} has no method named {$methodName}");
        }

        try {
            $parameters = [];
            $statements = $this->getClassUseStatements($reflectionClass);
            $reflectionMethod = $reflectionClass->getMethod($methodName);
            $docBlockParameterTypeHints = $this->getMethodDocBlockParameterTypeHints($reflectionClass, $reflectionMethod, $statements);
            foreach ($reflectionMethod->getParameters() as $parameter) {
                $parameters[] = InterfaceParameter::create(
                    $parameter->getName(),
                    TypeDescriptor::createWithDocBlock(
                        $parameter->getType() ? $this->expandParameterTypeHint($parameter->getType()->getName(), $statements, $reflectionClass) : null,
                        array_key_exists($parameter->getName(), $docBlockParameterTypeHints) ? $docBlockParameterTypeHints[$parameter->getName()] : ""
                    ),
                    $parameter->getType() ? $parameter->getType()->allowsNull() : true
                );
            }

            $returnType = $this->getReturnTypeDocBlockParameterTypeHint($reflectionClass, $reflectionMethod, $statements);
            $this->parameters = $parameters;
            $this->returnType = TypeDescriptor::create(
                $returnType
                    ? $returnType
                    : $this->expandParameterTypeHint((string)$reflectionMethod->getReturnType(), $statements, $reflectionClass)
            );
            $this->doesReturnTypeAllowNulls = $reflectionMethod->getReturnType() ? $reflectionMethod->getReturnType()->allowsNull() : true;
            $this->isStaticallyCalled = $reflectionMethod->isStatic();
        } catch (TypeDefinitionException $definitionException) {
            throw InvalidArgumentException::create("Interface {$this} has problem with type definition. {$definitionException->getMessage()}");
        }
    }

    /**
     * @param $alias
     * @return bool
     */
    private function hasUseStatementAlias($alias): bool
    {
        return count($alias) === 2;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @param \ReflectionMethod $methodReflection
     * @param string[] $statements
     * @return array
     * @throws \ReflectionException
     */
    private function getMethodDocBlockParameterTypeHints(\ReflectionClass $reflectionClass, \ReflectionMethod $methodReflection, array $statements): array
    {
        $docComment = $this->getDocComment($reflectionClass, $methodReflection);
        preg_match_all(self::METHOD_DOC_BLOCK_PARAMETERS_REGEX, $docComment, $matchedDocBlockParameterTypes);

        $docBlockParameterTypeHints = [];
        $matchAmount = count($matchedDocBlockParameterTypes[0]);
        for ($matchIndex = 0; $matchIndex < $matchAmount; $matchIndex++) {
            $docBlockParameterTypeHints[$matchedDocBlockParameterTypes[2][$matchIndex]] = $this->expandParameterTypeHint($matchedDocBlockParameterTypes[1][$matchIndex], $statements, $reflectionClass);
        }

        return $docBlockParameterTypeHints;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @param \ReflectionMethod $methodReflection
     * @return string
     * @throws \ReflectionException
     */
    private function getDocComment(\ReflectionClass $reflectionClass, \ReflectionMethod $methodReflection): string
    {
        $docComment = $methodReflection->getDocComment();
        if (!$docComment) {
            return "";
        }

        if (preg_match("/@inheritDoc/", $docComment)) {
            foreach ($reflectionClass->getInterfaceNames() as $interfaceName) {
                if (method_exists($interfaceName, $methodReflection->getName())) {
                    $docComment = (new \ReflectionMethod($interfaceName, $methodReflection->getName()))->getDocComment();
                }
            }
            if ($reflectionClass->getParentClass() && $reflectionClass->getParentClass()->hasMethod($methodReflection->getName())) {
                $docComment = $reflectionClass->getParentClass()->getMethod($methodReflection->getName())->getDocComment();
            }
        }

        return $docComment;
    }

    /**
     * @param string $parameterTypeHint
     * @param array $statements
     * @param \ReflectionClass $reflectionClass
     * @return string
     */
    private function expandParameterTypeHint(string $parameterTypeHint, array $statements, \ReflectionClass $reflectionClass): string
    {
        $multipleTypeHints = explode("|", $parameterTypeHint);
        $multipleTypeHints = is_array($multipleTypeHints) ? $multipleTypeHints : [$multipleTypeHints];

        if (!$parameterTypeHint) {
            return TypeDescriptor::UNKNOWN;
        }

        $fullNames = [];
        foreach ($multipleTypeHints as $typeHint) {
            if (class_exists($typeHint)) {
                $fullNames[] = $typeHint;
                continue;
            }

            if (strpos($typeHint, "[]") !== false) {
                $typeHint = "array<" . str_replace("[]", "", $typeHint) . ">";
            }

            $fullNames[] = $this->isInGlobalNamespace($typeHint)
                ? $typeHint
                : ($this->isFromDifferentNamespace($typeHint, $statements)
                    ? $this->getTypeHintFromUseNamespace($typeHint, $statements)
                    : $this->getWithClassNamespace($reflectionClass, $typeHint));
        }

        return implode("|", $fullNames);
    }

    /**
     * @param string $className
     * @return bool
     */
    private function isInGlobalNamespace(string $className): bool
    {
        if (in_array($className, [self::SELF_TYPE_HINT, self::STATIC_TYPE_HINT, self::THIS_TYPE_HINT])) {
            return false;
        }

        if (TypeDescriptor::isItTypeOfPrimitive($className) || TypeDescriptor::isItTypeOfVoid($className) || TypeDescriptor::isMixedType($className) || TypeDescriptor::isNull($className)) {
            return true;
        }

        if (preg_match(self::COLLECTION_TYPE_REGEX, $className, $matches)) {
            return TypeDescriptor::isItTypeOfScalar($matches[1]) || TypeDescriptor::isItTypeOfExistingClassOrInterface($matches[1]);
        }

        return count(explode("\\", $className)) == 2;
    }

    /**
     * @param string $classNameTypeHint
     * @param array $statements
     * @return bool
     */
    private function isFromDifferentNamespace(string $classNameTypeHint, array $statements): bool
    {
        $classNameTypeHint = $this->getRelatedClassNameFromTypeHint($classNameTypeHint);
        foreach ($statements as $classNameWithoutNamespace => $classNameWithNamespace) {
            if ($classNameTypeHint === $classNameWithoutNamespace) {
                return true;
            }
        }

        return
            array_key_exists($classNameTypeHint, $statements)
            ||
            count(explode("\\", $classNameTypeHint)) > 2;
    }

    /**
     * @param string $classNameTypeHint
     * @return mixed|string
     */
    private function getRelatedClassNameFromTypeHint(string $classNameTypeHint)
    {
        if (strpos($classNameTypeHint, "[]") !== false) {
            $classNameTypeHint = str_replace("[]", "", $classNameTypeHint);
        }
        if (preg_match(TypeDescriptor::COLLECTION_TYPE_REGEX, $classNameTypeHint, $classNameMatch)) {
            $classNameTypeHint = $classNameMatch[1];
        }

        return $classNameTypeHint;
    }

    /**
     * @param string $classNameTypeHint
     * @param array $useStatements
     * @return string
     */
    private function getTypeHintFromUseNamespace(string $classNameTypeHint, array $useStatements): string
    {
        $relatedClassName = $this->getRelatedClassNameFromTypeHint($classNameTypeHint);

        if (array_key_exists($relatedClassName, $useStatements)) {
            return str_replace($relatedClassName, $useStatements[$relatedClassName], $classNameTypeHint);
        }

        return $classNameTypeHint;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @param $parameterTypeHint
     * @return string
     */
    private function getWithClassNamespace(\ReflectionClass $reflectionClass, $parameterTypeHint): string
    {
        if ($parameterTypeHint === self::SELF_TYPE_HINT) {
            foreach ($reflectionClass->getInterfaceNames() as $interfaceName) {
                if (method_exists($interfaceName, $this->methodName)) {
                    return $interfaceName;
                }
            }
            $parentClass = $reflectionClass->getParentClass();
            if ($parentClass && $parentClass->hasMethod($this->methodName)) {
                return $parentClass->getName();
            }

            return $this->interfaceName;
        }

        if (in_array($parameterTypeHint, [self::STATIC_TYPE_HINT, self::THIS_TYPE_HINT])) {
            return $this->getInterfaceName();
        }

        $relatedClassName = $this->getRelatedClassNameFromTypeHint($parameterTypeHint);
        $typeHint = $reflectionClass->getNamespaceName() . "\\" . $relatedClassName;

        if (substr($typeHint, 0, 1) !== "\\") {
            $typeHint = "\\" . $typeHint;
        }

        return str_replace($relatedClassName, $typeHint, $parameterTypeHint);
    }

    /**
     * @return string
     */
    public function getInterfaceName(): string
    {
        return $this->interfaceName;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @param \ReflectionMethod $methodReflection
     * @param string[] $statements
     * @return string
     * @throws \ReflectionException
     */
    private function getReturnTypeDocBlockParameterTypeHint(\ReflectionClass $reflectionClass, \ReflectionMethod $methodReflection, array $statements): ?string
    {
        $docComment = $this->getDocComment($reflectionClass, $methodReflection);

        preg_match(self::METHOD_RETURN_TYPE_REGEX, $docComment, $matchedDocBlockReturnType);

        if (isset($matchedDocBlockReturnType[1])) {
            return $this->expandParameterTypeHint($matchedDocBlockReturnType[1], $statements, $reflectionClass);
        }

        return null;
    }

    /**
     * @param string|object $interfaceOrObjectName
     * @param string $methodName
     * @return InterfaceToCall
     * @throws InvalidArgumentException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function create($interfaceOrObjectName, string $methodName): self
    {
        $interface = $interfaceOrObjectName;
        if (is_object($interfaceOrObjectName)) {
            $interface = get_class($interfaceOrObjectName);
        }

        $annotationParser = InMemoryAnnotationRegistrationService::createFrom([$interface]);

        return new self($interface, $methodName, $annotationParser->getAnnotationsForClass($interface), $annotationParser->getAnnotationsForMethod($interface, $methodName));
    }

    /**
     * @return object[]
     */
    public function getMethodAnnotations(): iterable
    {
        return $this->methodAnnotations;
    }

    /**
     * @return object[]
     */
    public function getClassAnnotations(): iterable
    {
        return $this->classAnnotations;
    }

    /**
     * @param TypeDescriptor $className
     * @return bool
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function hasMethodAnnotation(TypeDescriptor $className): bool
    {
        foreach ($this->methodAnnotations as $methodAnnotation) {
            if (TypeDescriptor::createFromVariable($methodAnnotation)->equals($className)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param TypeDescriptor $className
     * @return bool
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function hasClassAnnotation(TypeDescriptor $className): bool
    {
        foreach ($this->classAnnotations as $classAnnotation) {
            if (TypeDescriptor::createFromVariable($classAnnotation)->equals($className)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param TypeDescriptor $className
     * @return object
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function getClassAnnotation(TypeDescriptor $className)
    {
        foreach ($this->classAnnotations as $classAnnotation) {
            if (TypeDescriptor::createFromVariable($classAnnotation)->equals($className)) {
                return $classAnnotation;
            }
        }

        throw InvalidArgumentException::create("Trying to retrieve not existing class annotation {$className} for {$this}");
    }

    /**
     * @param TypeDescriptor $className
     * @return object
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function getMethodAnnotation(TypeDescriptor $className)
    {
        foreach ($this->methodAnnotations as $methodAnnotation) {
            if (TypeDescriptor::createFromVariable($methodAnnotation)->equals($className)) {
                return $methodAnnotation;
            }
        }

        throw InvalidArgumentException::create("Trying to retrieve not existing method annotation {$className} for {$this}");
    }

    /**
     * @return bool
     */
    public function isStaticallyCalled(): bool
    {
        return $this->isStaticallyCalled;
    }

    /**
     * @return bool
     */
    public function hasReturnValue(): bool
    {
        return !$this->getReturnType()->isVoid();
    }

    /**
     * @return TypeDescriptor
     */
    public function getReturnType(): TypeDescriptor
    {
        return $this->returnType;
    }

    /**
     * @return bool
     */
    public function hasReturnTypeVoid(): bool
    {
        return $this->getReturnType()->isVoid();
    }

    /**
     * @return bool
     */
    public function hasReturnValueBoolean(): bool
    {
        return $this->getReturnType()->isBoolean();
    }

    /**
     * @return bool
     */
    public function doesItReturnIterable(): bool
    {
        return $this->getReturnType()->isIterable();
    }

    /**
     * @return string
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function getFirstParameterName(): string
    {
        return $this->getFirstParameter()->getName();
    }

    /**
     * @return InterfaceParameter
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function getFirstParameter(): InterfaceParameter
    {
        if ($this->parameterAmount() < 1) {
            throw InvalidArgumentException::create("Expecting {$this} to have at least one parameter, but got none");
        }

        return $this->getInterfaceParameters()[0];
    }

    /**
     * @return int
     */
    private function parameterAmount(): int
    {
        return count($this->getInterfaceParameters());
    }

    /**
     * @return array|InterfaceParameter[]
     */
    public function getInterfaceParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param int $index
     * @return InterfaceParameter
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function getParameterAtIndex(int $index): InterfaceParameter
    {
        if (!array_key_exists($index, $this->getInterfaceParameters())) {
            throw InvalidArgumentException::create("There is no parameter at index {$index} for {$this}");
        }

        return $this->parameters[$index];
    }

    /**
     * @return bool
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function hasFirstParameterMessageTypeHint(): bool
    {
        return $this->parameterAmount() > 0 && $this->getFirstParameter()->isMessage();
    }

    /**
     * @return bool
     */
    public function doesItReturnFuture(): bool
    {
        return $this->getReturnType()->isClassOfType(Future::class);
    }

    /**
     * @return bool
     */
    public function isReturnTypeUnknown(): bool
    {
        return $this->getReturnType()->isUnknown();
    }

    /**
     * @return bool
     */
    public function doesItReturnMessage(): bool
    {
        return $this->getReturnType()->isClassOfType(Message::class);
    }

    /**
     * @return TypeDescriptor
     */
    public function getInterfaceType() : TypeDescriptor
    {
        return $this->interfaceType;
    }

    /**
     * @return string
     */
    public function getMethodName(): string
    {
        return $this->methodName;
    }

    /**
     * @param string $methodName
     * @return bool
     */
    public function hasMethodName(string $methodName) : bool
    {
        return $this->getMethodName() === $methodName;
    }

    /**
     * @return bool
     */
    public function canItReturnNull(): bool
    {
        return is_null($this->returnType) || $this->doesReturnTypeAllowNulls;
    }

    /**
     * @return string
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function getFirstParameterTypeHint(): string
    {
        if ($this->parameterAmount() < 1) {
            throw InvalidArgumentException::create("Trying to get first parameter, but has none");
        }

        return $this->getFirstParameter()->getTypeHint();
    }

    /**
     * @param string $parameterName
     *
     * @return InterfaceParameter
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function getParameterWithName(string $parameterName): InterfaceParameter
    {
        foreach ($this->getInterfaceParameters() as $parameter) {
            if ($parameter->getName() == $parameterName) {
                return $parameter;
            }
        }

        throw InvalidArgumentException::create($this . " doesn't have parameter with name {$parameterName}");
    }

    /**
     * @return bool
     */
    public function hasMoreThanOneParameter(): bool
    {
        return $this->parameterAmount() > 1;
    }

    /**
     * @return bool
     */
    public function hasNoParameters(): bool
    {
        return $this->parameterAmount() == 0;
    }

    /**
     * @return bool
     */
    public function hasSingleArgument(): bool
    {
        return $this->parameterAmount() == 1;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "{$this->interfaceName}::{$this->methodName}";
    }

    /**
     * @param \ReflectionClass $interfaceReflection
     * @return array
     */
    private function getClassUseStatements(\ReflectionClass $interfaceReflection): array
    {
        $code = file_get_contents($interfaceReflection->getFileName());
        preg_match_all(self::CODE_USE_STATEMENTS_REGEX, $code, $foundUseStatements);

        $useStatements = [];
        $matchAmount = count($foundUseStatements[0]);
        for ($matchIndex = 0; $matchIndex < $matchAmount; $matchIndex++) {
            $className = $foundUseStatements[1][$matchIndex];
            $classNameAlias = null;
            if (($alias = explode(" as ", $className)) && $this->hasUseStatementAlias($alias)) {
                $className = $alias[0];
                $classNameAlias = $alias[1];
            }

            $splittedClassName = explode("\\", $className);
            if ($className[0] !== "\\") {
                $className = "\\" . $className;
            }
            if (!$classNameAlias) {
                $classNameAlias = end($splittedClassName);
            }

            $useStatements[$classNameAlias] = $className;
        }

        return $useStatements;
    }
}