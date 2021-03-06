<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Doctrine\Common\Annotations\AnnotationException;
use ReflectionException;
use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\PollerModule;
use SimplyCodedSoftware\Messaging\Config\ModuleReferenceSearchService;
use SimplyCodedSoftware\Messaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\Messaging\MessagingException;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\WithPoller\ServiceActivatorWithPollerExample;

/**
 * Class PollerModuleTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PollerModuleTest extends AnnotationConfigurationTest
{
    /**
     * @return mixed
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws MessagingException
     */
    public function test_registering_poller_for_endpoint()
    {
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerPollingMetadata(
                PollingMetadata::create("test-name")
                    ->setCron("* * * * *")
                    ->setInitialDelayInMilliseconds(2000)
                    ->setFixedRateInMilliseconds(130)
                    ->setErrorChannelName("errorChannel")
                    ->setMaxMessagePerPoll(5)
                    ->setHandledMessageLimit(10)
                    ->setMemoryLimitInMegaBytes(100)
            );

        $annotationRegistrationService = InMemoryAnnotationRegistrationService::createFrom([
            ServiceActivatorWithPollerExample::class
        ]);
        $annotationConfiguration = PollerModule::create($annotationRegistrationService);
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty());

        $this->assertEquals(
            $expectedConfiguration,
            $configuration
        );
    }
}