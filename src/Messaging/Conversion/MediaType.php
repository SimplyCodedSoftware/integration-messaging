<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Conversion;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Support\Assert;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class MediaType
 * @package SimplyCodedSoftware\Messaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class MediaType
{
    const TEXT_XML = "text/xml";
    const TEXT_JSON = "text/json";
    const TEXT_PLAIN = "text/plain";
    const TEXT_HTML = "text/html";
    const MULTIPART_FORM_DATA = "multipart/form-data";
    const IMAGE_PNG = "image/png";
    const IMAGE_JPEG = "image/jpeg";
    const IMAGE_GIF = "image/gif";
    const APPLICATION_XML = "application/xml";
    const APPLICATION_JSON = "application/json";
    const APPLICATION_FORM_URLENCODED = "application/x-www-form-urlencoded";
    const APPLICATION_ATOM_XML = "application/atom+xml";
    const APPLICATION_XHTML_XML = "application/xhtml+xml";
    const APPLICATION_OCTET_STREAM = "application/octet-stream";
    const APPLICATION_X_PHP_OBJECT = "application/x-php-object";
    const APPLICATION_X_PHP_SERIALIZED_OBJECT = "application/x-php-serialized-object";

    private const TYPE_PARAMETER = "type";

    /**
     * @var string
     */
    private $type;
    /**
     * @var string
     */
    private $subtype;
    /**
     * @var string[]
     */
    private $parameters = [];

    /**
     * MediaType constructor.
     * @param string $type
     * @param string $subtype
     * @param string[] $parameters
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function __construct(string $type, string $subtype, array $parameters)
    {
        Assert::notNullAndEmpty($type, "Primary type can't be empty");
        Assert::notNullAndEmpty($subtype, "Subtype type can't be empty");

        $this->type = $type;
        $this->subtype = $subtype;
        $this->parameters = $parameters;
    }

    /**
     * @param string $type primary type
     * @param string $subtype subtype
     * @return MediaType
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function create(string $type, string $subtype) : self
    {
        return new self($type, $subtype, []);
    }

    /**
     * @return MediaType
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createApplicationJson() : self
    {
        return self::parseMediaType(self::APPLICATION_JSON);
    }

    /**
     * @return MediaType
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createMultipartFormData() : self
    {
        return self::parseMediaType(self::MULTIPART_FORM_DATA);
    }

    /**
     * @return MediaType
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createApplicationOcetStream() : self
    {
        return self::parseMediaType(self::APPLICATION_OCTET_STREAM);
    }

    /**
     * @return MediaType
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createApplicationXPHPObject() : self
    {
        return self::parseMediaType(self::APPLICATION_X_PHP_OBJECT);
    }

    /**
     * @param string $type
     * @return MediaType
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createApplicationXPHPObjectWithTypeParameter(string $type) : self
    {
        if ($type === TypeDescriptor::UNKNOWN) {
            return self::parseMediaType(self::APPLICATION_X_PHP_OBJECT);
        }

        return self::parseMediaType(self::APPLICATION_X_PHP_OBJECT . ";type={$type}");
    }


    /**
     * @return MediaType
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createApplicationXPHPSerializedObject() : self
    {
        return self::parseMediaType(self::APPLICATION_X_PHP_SERIALIZED_OBJECT);
    }

    /**
     * @return MediaType
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createTextPlain() : self
    {
        return self::parseMediaType(self::TEXT_PLAIN);
    }

    /**
     * @param string $type
     * @param string $subtype
     * @param string[] $parameters
     * @return MediaType
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createWithParameters(string $type, string $subtype, array $parameters) : self
    {
        return new self($type, $subtype, $parameters);
    }

    /**
     * @param string $mediaType
     * @return MediaType
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public static function parseMediaType(string $mediaType) : self
    {
        $parsedMediaType = explode("/", $mediaType);

        Assert::keyExists($parsedMediaType, 0, "Passed media type has no type");
        Assert::keyExists($parsedMediaType, 1, "Passed media type has no subtype");
        $parametersToParse = explode(";", $parsedMediaType[1]);
        $subtype = array_shift($parametersToParse);
        $parameters = [];
        foreach ($parametersToParse as $parameterToParse) {
            $parameter = explode("=", $parameterToParse);
            $parameters[$parameter[0]] = $parameter[1];
        }

        return self::createWithParameters($parsedMediaType[0], $subtype, $parameters);
    }

    /**
     * @param string $mediaType
     * @return bool
     */
    public function hasType(string $mediaType) : bool
    {
        return $this->type === $mediaType;
    }

    /**
     * @param string $name
     * @param string $value
     * @return MediaType
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function addParameter(string $name, string $value) : self
    {
        return self::createWithParameters(
            $this->type,
            $this->subtype,
            array_merge($this->parameters, [$name => $value])
        );
    }

    /**
     * Returns primary type
     *
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getSubtype() : string
    {
        return $this->subtype;
    }

    /**
     * @return bool
     */
    public function isWildcardType() : bool
    {
        return $this->type === "*";
    }

    /**
     * @return bool
     */
    public function isWildcardSubtype() : bool
    {
        return $this->subtype === "*";
    }

    /**
     * @return string[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasParameter(string $name) : bool
    {
        return array_key_exists($name, $this->parameters);
    }

    /**
     * @return bool
     */
    public function hasTypeParameter() : bool
    {
        return $this->hasParameter(self::TYPE_PARAMETER);
    }

    /**
     * @return string
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function getTypeParameter() : string
    {
        return $this->getParameter(self::TYPE_PARAMETER);
    }

    /**
     * @param string $name
     * @return string
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function getParameter(string $name) : string
    {
        foreach ($this->parameters as $key => $value) {
            if ($key === $name) {
                return $value;
            }
        }

        throw InvalidArgumentException::create("Trying to access not existing media type parameter {$name} for {$this}");
    }

    /**
     * @param MediaType $other
     * @return bool
     */
    public function isCompatibleWith(MediaType $other) : bool
    {
        return ($this->type === $other->type || $this->isWildcardType() || $other->isWildcardType()) && ($this->subtype === $other->subtype || $this->isWildcardSubtype() || $other->isWildcardSubtype());
    }

    /**
     * @param string $otherMediaTypeToParse
     * @return bool
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function isCompatibleWithParsed(string $otherMediaTypeToParse) : bool
    {
        $other = self::parseMediaType($otherMediaTypeToParse);

        return ($this->type === $other->type || $this->isWildcardType() || $other->isWildcardType()) && ($this->subtype === $other->subtype || $this->isWildcardSubtype() || $other->isWildcardSubtype());
    }

    /**
     * @return string
     */
    public function toString() : string
    {
        return (string)$this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $parameters = "";
        foreach ($this->parameters as $key => $value) {
            $parameters .= ";{$key}={$value}";
        }

        return "{$this->type}/{$this->subtype}{$parameters}";
    }
}