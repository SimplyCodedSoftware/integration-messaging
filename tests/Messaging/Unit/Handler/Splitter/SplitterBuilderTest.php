<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Handler\Splitter;

use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Splitter\ServiceSplittingArrayPayload;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Splitter\WrongSplittingService;
use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\Splitter\SplitterBuilder;
use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\Messaging\Unit\MessagingTest;

/**
 * Class SplitterBuilderTest
 * @package SimplyCodedSoftware\Messaging\Handler\Splitter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SplitterBuilderTest extends MessagingTest
{
    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_splitting_incoming_message_where_service_returns_payloads()
    {
        $referenceName = "ref-a";
        $splitter = SplitterBuilder::create($referenceName, "splitToPayload");

        $service = new ServiceSplittingArrayPayload();
        $splitter = $splitter->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createWith([
                $referenceName => $service
            ])
        );

        $outputChannel = QueueChannel::create();
        $splitter->handle(MessageBuilder::withPayload([1, 2])->setReplyChannel($outputChannel)->build());

        $payload = $outputChannel->receive()->getPayload();
        $this->assertEquals(2, $payload);
        $this->assertEquals(1, $outputChannel->receive()->getPayload());
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function test_throwing_exception_if_splitter_does_not_return_array()
    {
        $referenceName = "ref-a";
        $splitter = SplitterBuilder::create($referenceName, "splittingWithReturnString");

        $service = new WrongSplittingService();

        $this->expectException(InvalidArgumentException::class);

        $splitter->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createWith([
                $referenceName => $service
            ])
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_creating_splitter_with_direct_reference()
    {
        $splitter = SplitterBuilder::createWithDirectObject(new ServiceSplittingArrayPayload(), "splitToPayload");

        $splitter = $splitter->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createEmpty()
        );

        $outputChannel = QueueChannel::create();
        $splitter->handle(MessageBuilder::withPayload([1, 2])->setReplyChannel($outputChannel)->build());

        $payload = $outputChannel->receive()->getPayload();
        $this->assertEquals(2, $payload);
        $this->assertEquals(1, $outputChannel->receive()->getPayload());
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_splitting_directly_from_message_without_service()
    {
        $splitter = SplitterBuilder::createMessagePayloadSplitter();

        $splitter = $splitter->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createEmpty()
        );

        $outputChannel = QueueChannel::create();
        $splitter->handle(MessageBuilder::withPayload([1, 2])->setReplyChannel($outputChannel)->build());

        $this->assertNotNull($outputChannel->receive());
        $this->assertNotNull($outputChannel->receive());
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_throwing_exception_if_message_for_payload_splitter_do_not_contains_array()
    {
        $splitter = SplitterBuilder::createMessagePayloadSplitter();

        $splitter = $splitter->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createEmpty()
        );

        $this->expectException(MessagingException::class);

        $splitter->handle(MessageBuilder::withPayload("test")->setReplyChannel(QueueChannel::create())->build());
    }

    public function test_converting_to_string()
    {
        $inputChannelName = 'inputChannel';
        $endpointName = "someName";

        $this->assertEquals(
            SplitterBuilder::create("ref-name", "method-name")
                ->withInputChannelName($inputChannelName)
                ->withEndpointId($endpointName),
            sprintf("Splitter - %s:%s with name `%s` for input channel `%s`", "ref-name", "method-name", $endpointName, $inputChannelName)
        );
    }
}