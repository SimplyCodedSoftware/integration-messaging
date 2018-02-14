<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation;

use Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined\ServiceActivatorWithAllConfigurationDefined;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToReferenceServiceAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToReferenceServiceParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class ParameterConverterAnnotationFactoryTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ParameterConverterAnnotationFactoryTest extends MessagingTest
{
    public function test_creating_with_class_name_as_reference_name_if_no_reference_passed()
    {
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();
        $parameterConverterAnnotation = new MessageToReferenceServiceAnnotation();
        $parameterConverterAnnotation->parameterName = "object";

        $relatedClassName = ServiceActivatorWithAllConfigurationDefined::class;
        $methodName = "sendMessage";
        $messageHandler = ServiceActivatorBuilder::create($relatedClassName, $methodName);
        $messageHandler
            ->withMethodParameterConverters([
                MessageToReferenceServiceParameterConverterBuilder::create(
                    $parameterConverterAnnotation->parameterName,
                    \stdClass::class,
                    $messageHandler
                )
            ]);
        $messageHandler
            ->registerRequiredReference(\stdClass::class);

        $messageHandlerBuilderToCompare = ServiceActivatorBuilder::create($relatedClassName, $methodName);
        $parameterConverterAnnotationFactory->configureParameterConverters(
            $messageHandlerBuilderToCompare,
            $relatedClassName,
            $methodName,
            [$parameterConverterAnnotation]
        );

        $this->assertEquals(
            $messageHandler,
            $messageHandlerBuilderToCompare
        );
    }
}