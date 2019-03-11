<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\Support\Assert;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Interface MethodInterceptor
 * @package SimplyCodedSoftware\Messaging\MethodInterceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AroundMethodInterceptor
{
    /**
     * @var object
     */
    private $referenceToCall;
    /**
     * @var InterfaceToCall
     */
    private $interceptorInterfaceToCall;

    /**
     * MethodInterceptor constructor.
     * @param object $referenceToCall
     * @param InterfaceToCall $interfaceToCall
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function __construct($referenceToCall, InterfaceToCall $interfaceToCall)
    {
        Assert::isObject($referenceToCall, "Method Interceptor should point to instance not class name");

        if ($interfaceToCall->hasReturnValue() && !$this->hasMethodInvocationParameter($interfaceToCall)) {
            throw InvalidArgumentException::create("Trying to register {$interfaceToCall} as Around Advice which can return value, but doesn't control invocation using " . MethodInvocation::class . ". Have you wanted to register Before/After Advice or forgot to type hint MethodInvocation?");
        }

        $this->referenceToCall = $referenceToCall;
        $this->interceptorInterfaceToCall = $interfaceToCall;
    }

    /**
     * @param object $referenceToCall
     * @param string $methodName
     * @param InterfaceToCallRegistry $interfaceToCallRegistry
     * @return AroundMethodInterceptor
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createWith($referenceToCall, string $methodName, InterfaceToCallRegistry $interfaceToCallRegistry) : self
    {
        $interfaceToCall = $interfaceToCallRegistry->getFor($referenceToCall, $methodName);

        return new self($referenceToCall, $interfaceToCall);
    }

    /**
     * @param $referenceToCall
     * @param InterfaceToCall $interfaceToCall
     * @return AroundMethodInterceptor
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createWithInterface($referenceToCall, InterfaceToCall $interfaceToCall) : self
    {
        return new self($referenceToCall, $interfaceToCall);
    }

    /**
     * @param MethodInvocation $methodInvocation
     * @param MethodCall $methodCall
     * @param Message $requestMessage
     *
     * @return mixed
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function invoke(MethodInvocation $methodInvocation, MethodCall $methodCall, Message $requestMessage)
    {
        $methodInvocationType = TypeDescriptor::create(MethodInvocation::class);

        $hasMethodInvocation = false;
        $argumentsToCallInterceptor = [];
        $interceptedInstanceType = TypeDescriptor::createFromVariable($methodInvocation->getInterceptedInstance());
        $messageType = TypeDescriptor::create(Message::class);

        foreach ($this->interceptorInterfaceToCall->getInterfaceParameters() as $parameter) {
            $resolvedArgument = null;

            foreach ($methodCall->getMethodArguments() as $methodArgument) {
                if ($methodArgument->hasSameTypeAs($parameter)) {
                    $resolvedArgument = $methodArgument->value();
                }
            }

            if (!$resolvedArgument && $parameter->hasType($methodInvocationType)) {
                $hasMethodInvocation = true;
                $resolvedArgument = $methodInvocation;
            }

            if (!$resolvedArgument && $parameter->hasType($interceptedInstanceType)) {
                $resolvedArgument = $methodInvocation->getInterceptedInstance();
            }

            if (!$resolvedArgument && $parameter->hasType($messageType)) {
                $resolvedArgument = $requestMessage;
            }

            if (!$resolvedArgument) {
                if ($methodInvocation->getInterceptedInterface()->hasMethodAnnotation($parameter->getTypeDescriptor())) {
                    $resolvedArgument = $methodInvocation->getInterceptedInterface()->getMethodAnnotation($parameter->getTypeDescriptor());
                }
                if ($methodInvocation->getInterceptedInterface()->hasClassAnnotation($parameter->getTypeDescriptor())) {
                    $resolvedArgument = $methodInvocation->getInterceptedInterface()->getClassAnnotation($parameter->getTypeDescriptor());
                }
            }

            if (!$resolvedArgument && !$parameter->doesAllowNulls()) {
                throw MethodInvocationException::create("{$this->interceptorInterfaceToCall} can't resolve argument for parameter with name `{$parameter->getName()}`. Maybe your docblock type hint is not correct?");
            }

            $argumentsToCallInterceptor[] = $resolvedArgument;
        }

        $returnValue = call_user_func_array(
            [$this->referenceToCall, $this->interceptorInterfaceToCall->getMethodName()],
            $argumentsToCallInterceptor
        );

        if (!$hasMethodInvocation) {
            return $methodInvocation->proceed();
        }

        return $returnValue;
    }

    /**
     * @param InterfaceToCall $interfaceToCall
     * @return bool
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function hasMethodInvocationParameter(InterfaceToCall $interfaceToCall): bool
    {
        $methodInvocation = TypeDescriptor::create(MethodInvocation::class);
        foreach ($interfaceToCall->getInterfaceParameters() as $interfaceParameter) {
            if ($interfaceParameter->hasType($methodInvocation)) {
                return true;
            }
        }

        return false;
    }
}