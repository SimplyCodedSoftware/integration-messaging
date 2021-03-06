<?php

namespace SimplyCodedSoftware\Messaging\Support;

/**
 * Class Assert
 * @package SimplyCodedSoftware\Messaging\Support
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class Assert
{

    /**
     * @param bool $toCheck
     * @param string $message
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function isTrue(bool $toCheck, string $message) : void
    {
        if (!$toCheck) {
            throw InvalidArgumentException::create($message);
        }
    }

    /**
     * @param $valueToCheck
     * @param string $exceptionMessage
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function notNullAndEmpty($valueToCheck, string $exceptionMessage) : void
    {
        if (!$valueToCheck) {
            throw InvalidArgumentException::create($exceptionMessage);
        }
    }

    /**
     * @param array $array
     * @param string|int $requiredKey
     * @param string $exceptionMessage
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function keyExists(array $array, $requiredKey, string $exceptionMessage) : void
    {
        if (!isset($array[$requiredKey])) {
            throw InvalidArgumentException::create($exceptionMessage);
        }
    }

    /**
     * @param $valueToCheck
     * @param string $exceptionMessage
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function notNull($valueToCheck, string $exceptionMessage) : void
    {
        if (is_null($valueToCheck)) {
            throw InvalidArgumentException::create($exceptionMessage);
        }
    }

    /**
     * @param array $arrayToCheck
     * @param string $className
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function allInstanceOfType(array $arrayToCheck, string $className) : void
    {
        foreach ($arrayToCheck as $classToCompare) {
            Assert::isSubclassOf($classToCompare, $className, "");
        }
    }

    /**
     * @param $objectToCheck
     * @param string $className
     * @param string $message
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function isSubclassOf($objectToCheck, string $className, string $message) : void
    {
        $classToCheck = get_class($objectToCheck);
        if ($classToCheck !== $className && !is_subclass_of($objectToCheck, $className)) {
            throw InvalidArgumentException::create("{$message}. Passed argument should be of type {$className} and got {$classToCheck}.");
        }
    }

    /**
     * @param string $interfaceToCheck
     * @param string $message
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function isInterface(string $interfaceToCheck, string $message) : void
    {
        if (!interface_exists($interfaceToCheck)) {
            throw InvalidArgumentException::create($message);
        }
    }

    /**
     * @param $valueToCheck
     * @param string $exceptionMessage
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function isObject($valueToCheck, string $exceptionMessage) : void
    {
        if (!is_object($valueToCheck)) {
            throw InvalidArgumentException::create($exceptionMessage);
        }
    }

    public static function isIterable($valueToCheck, string $exceptionMessage) : void
    {
        if (!is_iterable($valueToCheck)) {
            throw InvalidArgumentException::create($exceptionMessage);
        }
    }
}