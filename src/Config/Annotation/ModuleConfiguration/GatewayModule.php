<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\GatewayAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ParameterToMessage\ParameterToHeaderAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ParameterToMessage\ParameterToPayloadAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ParameterToMessage\ParameterToStaticHeaderAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistration;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationObserver;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\ParameterToHeaderConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\ParameterToPayloadConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\ParameterToStaticHeaderConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class AnnotationGatewayConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationToBuilder
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class GatewayModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public const MODULE_NAME = 'gatewayModule';

    /**
     * @var AnnotationRegistration[]
     */
    private $gatewayRegistrations;

    /**
     * AnnotationGatewayConfiguration constructor.
     *
     * @param AnnotationRegistration[] $gatewayRegistrations
     */
    private function __construct(array $gatewayRegistrations)
    {
        $this->gatewayRegistrations = $gatewayRegistrations;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService): AnnotationModule
    {
        return new self($annotationRegistrationService->findRegistrationsFor(MessageEndpointAnnotation::class, GatewayAnnotation::class));
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::MODULE_NAME;
    }

    /**
     * @inheritDoc
     */
    public function preConfigure(array $moduleExtensions, ConfigurationObserver $configurationObserver): void
    {
        foreach ($this->gatewayRegistrations as $gatewayRegistration) {
            $configurationObserver->notifyGatewayBuilderWasRegistered($gatewayRegistration->getReferenceName(), $gatewayRegistration->getClassWithAnnotation(), $gatewayRegistration->getClassWithAnnotation());
        }
    }

    /**
     * @inheritDoc
     */
    public function registerWithin(Configuration $configuration, array $moduleExtensions, ConfigurationVariableRetrievingService $configurationVariableRetrievingService, ReferenceSearchService $referenceSearchService): void
    {
        foreach ($this->gatewayRegistrations as $annotationRegistration) {
            /** @var GatewayAnnotation $annotation */
            $annotation = $annotationRegistration->getAnnotationForMethod();

            $parameterConverters = [];
            foreach ($annotation->parameterConverters as $parameterToMessage) {
                if ($parameterToMessage instanceof ParameterToPayloadAnnotation) {
                    $parameterConverters[] = ParameterToPayloadConverterBuilder::create($parameterToMessage->parameterName);
                } else if ($parameterToMessage instanceof ParameterToHeaderAnnotation) {
                    $parameterConverters[] = ParameterToHeaderConverterBuilder::create($parameterToMessage->parameterName, $parameterToMessage->headerName);
                } else if ($parameterToMessage instanceof ParameterToStaticHeaderAnnotation) {
                    $parameterConverters[] = ParameterToStaticHeaderConverterBuilder::create($parameterToMessage->headerName, $parameterToMessage->headerValue);
                }
            }

            $gateway = GatewayProxyBuilder::create($annotationRegistration->getReferenceName(), $annotationRegistration->getClassWithAnnotation(), $annotationRegistration->getMethodName(), $annotation->requestChannel)
                ->withMillisecondTimeout(1)
                ->withParameterToMessageConverters($parameterConverters);

            $configuration->registerGatewayBuilder($gateway);
        }
    }

    /**
     * @inheritDoc
     */
    public function postConfigure(ConfiguredMessagingSystem $configuredMessagingSystem): void
    {
        return;
    }
}