<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Handler\Router;

use Test\SimplyCodedSoftware\Messaging\Fixture\Router\MultipleChannelRouter;
use Test\SimplyCodedSoftware\Messaging\Fixture\Router\Order;
use Test\SimplyCodedSoftware\Messaging\Fixture\Router\SingleChannelRouter;
use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\DestinationResolutionException;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\Router\RouterBuilder;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\Messaging\Unit\MessagingTest;

/**
 * Class RouterBuilderTest
 * @package SimplyCodedSoftware\Messaging\Handler\Router
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RouterBuilderTest extends MessagingTest
{
    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_routing_message_to_single_channel()
    {
        $chanelName = 'buyChannel';
        $targetChannel = QueueChannel::create();
        $objectToInvokeReference = "service-a";

        $router = RouterBuilder::create($objectToInvokeReference, 'pick')
                    ->build(
                        InMemoryChannelResolver::createFromAssociativeArray([
                            $chanelName => $targetChannel
                        ]),
                        InMemoryReferenceSearchService::createWith([
                            $objectToInvokeReference => SingleChannelRouter::createWithChosenChannelName($chanelName)
                        ])
                    );

        $message = MessageBuilder::withPayload('some')
            ->build();

        $router->handle($message);

        $this->assertMessages($message, $targetChannel->receive());
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_routing_message_to_multiple_channels()
    {
        $targetChannel1 = QueueChannel::create();
        $targetChannel2 = QueueChannel::create();

        $objectToInvokeReference = "service-a";
        $router = RouterBuilder::create($objectToInvokeReference, 'pick')
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    'channel1' => $targetChannel1,
                    'channel2' => $targetChannel2
                ]),
                InMemoryReferenceSearchService::createWith([
                    $objectToInvokeReference => MultipleChannelRouter::createWithChosenChannelName([
                        'channel1',
                        'channel2'
                    ])
                ])
            );

        $message = MessageBuilder::withPayload('some')
            ->build();

        $router->handle($message);

        $this->assertMessages($message, $targetChannel1->receive());
        $this->assertMessages($message, $targetChannel2->receive());
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_throwing_exception_if_resolution_is_required()
    {
        $objectToInvokeReference = "service-a";
        $router = RouterBuilder::create($objectToInvokeReference, 'pick')
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([]),
                InMemoryReferenceSearchService::createWith([
                    $objectToInvokeReference => MultipleChannelRouter::createWithChosenChannelName([])
                ])
            );

        $message = MessageBuilder::withPayload('some')
            ->build();

        $this->expectException(DestinationResolutionException::class);

        $router->handle($message);
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_if_no_resolution_required_not_throwing_exception_when_no_resolution()
    {
        $objectToInvokeReference = "service-a";
        $router = RouterBuilder::create( $objectToInvokeReference, 'pick')
            ->setResolutionRequired(false)
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([]),
                InMemoryReferenceSearchService::createWith([
                    $objectToInvokeReference => MultipleChannelRouter::createWithChosenChannelName([])
                ])
            );

        $message = MessageBuilder::withPayload('some')
            ->build();

        $router->handle($message);

        $this->assertTrue(true);
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_routing_with_payload_type()
    {
        $targetChannel1 = QueueChannel::create();
        $targetChannel2 = QueueChannel::create();
        $inputChannelName = "input";

        $router = RouterBuilder::createPayloadTypeRouter([
            \stdClass::class => 'channel1',
            Order::class => 'channel2'
        ])
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    'channel1' => $targetChannel1,
                    'channel2' => $targetChannel2,
                    $inputChannelName => DirectChannel::create()
                ]),
                InMemoryReferenceSearchService::createEmpty()
            );

        $message = MessageBuilder::withPayload(new \stdClass())
            ->build();

        $router->handle($message);

        $this->assertMessages($message, $targetChannel1->receive());
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_routing_to_default_when_not_hit()
    {
        $targetChannel = QueueChannel::create();

        $defaultResolutionChannel = 'default';
        $router                   = RouterBuilder::createPayloadTypeRouter([
            Order::class => 'channel2'
        ])
            ->withDefaultResolutionChannel($defaultResolutionChannel)
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $defaultResolutionChannel => $targetChannel
                ]),
                InMemoryReferenceSearchService::createEmpty()
            );

        $message = MessageBuilder::withPayload(new \stdClass())
            ->build();

        $router->handle($message);

        $this->assertMessages($message, $targetChannel->receive());
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_routing_by_payload_type_without_mapping()
    {
        $targetChannel = QueueChannel::create();
        $inputChannelName = "input";

        $router = RouterBuilder::createPayloadTypeRouterByClassName()
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    \stdClass::class => $targetChannel,
                    $inputChannelName => DirectChannel::create()
                ]),
                InMemoryReferenceSearchService::createEmpty()
            );

        $message = MessageBuilder::withPayload(new \stdClass())
            ->build();

        $router->handle($message);

        $this->assertMessages($message, $targetChannel->receive());
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_routing_with_header_value()
    {
        $targetChannel1 = QueueChannel::create();
        $targetChannel2 = QueueChannel::create();
        $headerName = 'type';

        $router = RouterBuilder::createHeaderValueRouter($headerName, [
            'private' => 'channel1',
            'public' => 'channel2'
        ])
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    'channel1' => $targetChannel1,
                    'channel2' => $targetChannel2
                ]),
                InMemoryReferenceSearchService::createEmpty()
            );

        $message = MessageBuilder::withPayload('some')
                    ->setHeader($headerName, 'private')
                    ->build();

        $router->handle($message);

        $this->assertMessages($message, $targetChannel1->receive());
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_throwing_exception_if_payload_is_not_object()
    {
        $router = RouterBuilder::createPayloadTypeRouterByClassName()
            ->build(
                InMemoryChannelResolver::createEmpty(),
                InMemoryReferenceSearchService::createEmpty()
            );

        $message = MessageBuilder::withPayload("some")
            ->build();

        $this->expectException(InvalidArgumentException::class);

        $router->handle($message);
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \Exception
     */
    public function test_recipient_list_router()
    {
        $channel1 = QueueChannel::create();
        $channel2 = QueueChannel::create();

        $router           = RouterBuilder::createRecipientListRouter(["channel1", "channel2"])
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    "channel1" => $channel1,
                    "channel2" => $channel2
                ]),
                InMemoryReferenceSearchService::createEmpty()
            );

        $message = MessageBuilder::withPayload("some")->build();

        $router->handle($message);

        $this->assertEquals($message, $channel1->receive());
        $this->assertEquals($message, $channel2->receive());
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_applying_sequence_to_recipient_list()
    {
        $channel1 = QueueChannel::create();
        $channel2 = QueueChannel::create();

        $router           = RouterBuilder::createRecipientListRouter(["channel1", "channel2"])
            ->withApplySequence(true)
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    "channel1" => $channel1,
                    "channel2" => $channel2
                ]),
                InMemoryReferenceSearchService::createEmpty()
            );

        $message = MessageBuilder::withPayload("some")->build();

        $router->handle($message);

        $firstMessage = $channel1->receive();
        $this->assertEquals(1, $firstMessage->getHeaders()->get(MessageHeaders::SEQUENCE_NUMBER));
        $this->assertEquals(2, $firstMessage->getHeaders()->get(MessageHeaders::SEQUENCE_SIZE));
        $secondMessage = $channel2->receive();
        $this->assertEquals(2, $secondMessage->getHeaders()->get(MessageHeaders::SEQUENCE_NUMBER));
        $this->assertEquals(2, $secondMessage->getHeaders()->get(MessageHeaders::SEQUENCE_SIZE));
    }

    public function test_converting_to_string()
    {
        $inputChannelName = 'inputChannel';
        $endpointName = "someName";

        $this->assertEquals(
            RouterBuilder::create("ref-name", "method-name")
                ->withInputChannelName($inputChannelName)
                ->withEndpointId($endpointName),
            sprintf("Router for input channel `%s` with name `%s`", $inputChannelName, $endpointName)
        );
    }
}