<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Endpoint\Poller;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Endpoint\EntrypointGateway;
use SimplyCodedSoftware\Messaging\Endpoint\NullAcknowledgementCallback;
use SimplyCodedSoftware\Messaging\Endpoint\NullConsumerLifecycle;
use SimplyCodedSoftware\Messaging\Endpoint\PollingConsumer\PollerTaskExecutor;
use SimplyCodedSoftware\Messaging\Endpoint\StoppableConsumer;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class PollerTaskExecutorTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Endpoint\Poller
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PollerTaskExecutorTest extends TestCase
{
    public function test_passing_message_to_gateway()
    {
        $message = MessageBuilder::withPayload("some")->build();

        $gateway = $this->createMock(EntrypointGateway::class);
        $gateway
            ->expects($this->once())
            ->method("execute")
            ->withAnyParameters();

        $pollableChannel = QueueChannel::create();
        $pollableChannel->send($message);

        $pollingExecutor = $this->createPoller($pollableChannel, $gateway);
        $pollingExecutor->execute();
    }

    /**
     * @param QueueChannel $pollableChannel
     * @param MockObject $gateway
     * @return PollerTaskExecutor
     */
    private function createPoller(QueueChannel $pollableChannel, MockObject $gateway): PollerTaskExecutor
    {
        $pollingExecutor = new PollerTaskExecutor($pollableChannel, $gateway);
        return $pollingExecutor;
    }

    public function test_acking_message_when_ack_available_in_message_header()
    {
        $acknowledgementCallback = NullAcknowledgementCallback::create();
        $message = MessageBuilder::withPayload("some")
            ->setHeader(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION, "amqpAcker")
            ->setHeader("amqpAcker", $acknowledgementCallback)
            ->build();

        $gateway = $this->createMock(EntrypointGateway::class);

        $pollableChannel = QueueChannel::create();
        $pollableChannel->send($message);

        $pollingExecutor = $this->createPoller($pollableChannel, $gateway);
        $pollingExecutor->execute();

        $this->assertTrue($acknowledgementCallback->isAcked());
    }

    public function test_requeing_message_on_gateway_failure()
    {
        $acknowledgementCallback = NullAcknowledgementCallback::create();
        $message = MessageBuilder::withPayload("some")
            ->setHeader(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION, "amqpAcker")
            ->setHeader("amqpAcker", $acknowledgementCallback)
            ->build();

        $gateway = $this->createMock(EntrypointGateway::class);
        $gateway
            ->expects($this->once())
            ->method("execute")
            ->willThrowException(InvalidArgumentException::create("gateway test exception"));

        $pollableChannel = QueueChannel::create();
        $pollableChannel->send($message);

        $pollingExecutor = $this->createPoller($pollableChannel, $gateway);
        $pollingExecutor->execute();

        $this->assertTrue($acknowledgementCallback->isRequeued());
    }
}