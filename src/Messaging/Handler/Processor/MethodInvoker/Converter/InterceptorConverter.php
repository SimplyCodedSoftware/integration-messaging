<?php


namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter;

use SimplyCodedSoftware\Messaging\Handler\InterfaceParameter;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Class AnnotationInterceptorConverter
 * @package SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InterceptorConverter implements ParameterConverter
{
    /**
     * @var InterfaceToCall
     */
    private $interceptedInterface;
    /**
     * @var object[]
     */
    private $endpointAnnotations;

    /**
     * AnnotationInterceptorConverter constructor.
     *
     * @param InterfaceToCall $interceptedInterface
     * @param object[] $endpointAnnotations
     */
    public function __construct(InterfaceToCall $interceptedInterface, array $endpointAnnotations)
    {
        $this->interceptedInterface = $interceptedInterface;
        $this->endpointAnnotations = $endpointAnnotations;
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(InterfaceToCall $interfaceToCall, InterfaceParameter $relatedParameter, Message $message, array $endpointAnnotations)
    {
        if ($relatedParameter->hasType(TypeDescriptor::create(InterfaceToCall::class))) {
            return $this->interceptedInterface;
        }

        foreach ($this->endpointAnnotations as $endpointAnnotation) {
            if ($relatedParameter->hasType(TypeDescriptor::createFromVariable($endpointAnnotation))) {
                return $endpointAnnotation;
            }
        }

        if ($this->interceptedInterface->hasMethodAnnotation($relatedParameter->getTypeDescriptor())) {
            return $this->interceptedInterface->getMethodAnnotation($relatedParameter->getTypeDescriptor());
        }

        return $this->interceptedInterface->getClassAnnotation($relatedParameter->getTypeDescriptor());
    }

    /**
     * @inheritDoc
     */
    public function isHandling(InterfaceParameter $parameter): bool
    {
        return
            $parameter->hasType(TypeDescriptor::create(InterfaceToCall::class))
            || $this->interceptedInterface->hasMethodAnnotation($parameter->getTypeDescriptor())
            || $this->interceptedInterface->hasClassAnnotation($parameter->getTypeDescriptor());
    }
}