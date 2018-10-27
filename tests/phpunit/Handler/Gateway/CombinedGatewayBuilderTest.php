<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway;

use Fixture\Handler\Gateway\MultipleMethodsGatewayExample;
use Fixture\Service\ServiceInterface\ServiceInterfaceSendOnly;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\CombinedGatewayDefinition;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\CombinedGatewayBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\MessagingException;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class MultipleMethodGatewayBuilder
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CombinedGatewayBuilderTest extends TestCase
{
    /**
     * @throws MessagingException
     */
    public function test_proxy_to_specific_gateway()
    {
        $requestChannelNameGatewayOne = "requestChannel1";
        $requestChannelGatewayOne = QueueChannel::create();
        $gatewayProxyBuilderOne = GatewayProxyBuilder::create('ref-name', MultipleMethodsGatewayExample::class, 'execute1', $requestChannelNameGatewayOne);
        $requestChannelNameGatewayTwo = "requestChannel2";
        $requestChannelGatewayTwo = QueueChannel::create();
        $gatewayProxyBuilderTwo = GatewayProxyBuilder::create('ref-name', MultipleMethodsGatewayExample::class, 'execute2', $requestChannelNameGatewayTwo);

        $multipleGatewayBuilder = CombinedGatewayBuilder::create("ref-name", MultipleMethodsGatewayExample::class,
            [
                CombinedGatewayDefinition::create($gatewayProxyBuilderOne, "execute1"),
                CombinedGatewayDefinition::create($gatewayProxyBuilderTwo, "execute2")
            ]
        );
        /** @var MultipleMethodsGatewayExample $gateway */
        $gateway = $multipleGatewayBuilder->build(
            InMemoryReferenceSearchService::createEmpty(),
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    $requestChannelNameGatewayOne => $requestChannelGatewayOne,
                    $requestChannelNameGatewayTwo => $requestChannelGatewayTwo
                ]
            )
        );

        $gateway->execute1('some1');
        $payload = $requestChannelGatewayOne->receive()->getPayload();
        $this->assertEquals($payload,'some1');
        $this->assertNull($requestChannelGatewayTwo->receive());

        $gateway->execute2('some2');
        $this->assertNull($requestChannelGatewayOne->receive());
        $this->assertEquals($requestChannelGatewayTwo->receive()->getPayload(),'some2');
    }

    public function test_throwing_exception_if_interface_has_no_combined_method()
    {
        $this->expectException(InvalidArgumentException::class);

        $requestChannelNameGatewayOne = "requestChannel1";
        $gatewayProxyBuilderOne = GatewayProxyBuilder::create('ref-name', MultipleMethodsGatewayExample::class, 'execute1', $requestChannelNameGatewayOne);

        CombinedGatewayBuilder::create("ref-name", MultipleMethodsGatewayExample::class,
            [
                CombinedGatewayDefinition::create($gatewayProxyBuilderOne, "notExisting")
            ]
        );
    }
}