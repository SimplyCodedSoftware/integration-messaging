<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Handler\Chain;

use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Transformer\PassThroughTransformer;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Transformer\StdClassTransformer;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Transformer\StringTransformer;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\CalculatingService;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\Chain\ChainMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\Enricher\Converter\EnrichHeaderWithExpressionBuilder;
use SimplyCodedSoftware\Messaging\Handler\Enricher\Converter\EnrichHeaderWithValueBuilder;
use SimplyCodedSoftware\Messaging\Handler\Enricher\EnricherBuilder;
use SimplyCodedSoftware\Messaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\Logger\LoggingHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\Router\RouterBuilder;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\Messaging\Handler\SymfonyExpressionEvaluationAdapter;
use SimplyCodedSoftware\Messaging\Handler\Transformer\TransformerBuilder;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class ChainMessageHandlerBuilderTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Handler\Chain
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ChainMessageHandlerBuilderTest extends TestCase
{
    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \Exception
     */
    public function test_chaining_with_single_message_handler()
    {
        $replyChannel = QueueChannel::create();
        $requestPayload = "some";
        $messageHandler = TransformerBuilder::createHeaderEnricher([
            "token" => "123"
        ]);

        $this->createChainHandlerAndHandle([$messageHandler], $requestPayload, $replyChannel);

        $this->assertEquals(
            "123",
            $replyChannel->receive()->getHeaders()->get("token")
        );
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_chaining_with_two_message_handlers()
    {
        $replyChannel = QueueChannel::create();
        $requestPayload = "some";

        $this->createChainHandlerAndHandle(
            [
                TransformerBuilder::createHeaderEnricher([
                    "token" => "123"
                ]),
                TransformerBuilder::createHeaderEnricher([
                    "hax" => "x"
                ])
            ],
            $requestPayload,
            $replyChannel
        );

        $message = $replyChannel->receive();
        $this->assertEquals(
            "123",
            $message->getHeaders()->get("token")
        );
        $this->assertEquals(
            "x",
            $message->getHeaders()->get("hax")
        );
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_with_chain_handler_at_the_end()
    {
        $replyChannel = QueueChannel::create();
        $requestPayload = 0;

        $this->createChainHandlerAndHandle(
            [
                ChainMessageHandlerBuilder::create()
                    ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), "sum"))
                    ->chain(
                        ChainMessageHandlerBuilder::create()
                            ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), "sum"))
                            ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), "sum"))
                            ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), "sum"))
                    )
            ],
            $requestPayload,
            $replyChannel
        );

        $message = $replyChannel->receive();
        $this->assertEquals(
            4,
            $message->getPayload()
        );
    }

    public function test_chaining_payload_transformers()
    {
        $replyChannel = QueueChannel::create();
        $requestPayload = "x";

        $this->createChainHandlerAndHandle(
            [
                TransformerBuilder::createWithDirectObject(new StdClassTransformer(), "transform"),
                TransformerBuilder::createWithDirectObject( new StringTransformer(), "transform"),
            ],
            $requestPayload,
            $replyChannel
        );

        $this->assertEquals(
            "some",
            $replyChannel->receive()->getPayload()
        );
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_chaining_with_other_chain_inside()
    {
        $replyChannel = QueueChannel::create();
        $requestPayload = "x";

        $this->createChainHandlerAndHandle(
            [
                TransformerBuilder::createWithDirectObject(new StdClassTransformer(), "transform"),
                TransformerBuilder::createWithDirectObject( new StringTransformer(), "transform"),
                ChainMessageHandlerBuilder::create()
                    ->chain(TransformerBuilder::createWithDirectObject(new StdClassTransformer(), "transform"))
            ],
            $requestPayload,
            $replyChannel
        );

        $this->assertEquals(
            new \stdClass(),
            $replyChannel->receive()->getPayload()
        );
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_chaining_with_other_chain_at_the_beginning_of_flow()
    {
        $replyChannel = QueueChannel::create();
        $requestPayload = ["some" => "bla"];

        $this->createChainHandlerAndHandle(
            [
                EnricherBuilder::create([
                   EnrichHeaderWithValueBuilder::create("awesome", "yes")
                ]),
                ChainMessageHandlerBuilder::create()
                    ->chain(TransformerBuilder::createWithDirectObject( new StdClassTransformer(), "transform"))
                    ->chain(TransformerBuilder::createHeaderEnricher(["superAwesome" => "no"])),
                TransformerBuilder::createHeaderEnricher(["superAwesome" => "yes"])
            ],
            $requestPayload,
            $replyChannel
        );

        $message = $replyChannel->receive();
        $this->assertEquals(
            new \stdClass(),
            $message->getPayload()
        );
        $this->assertEquals(
            "yes",
            $message->getHeaders()->get("awesome")
        );
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_setting_output_channel()
    {
        $outputChannelName = "outputChannelName";
        $outputChannel = QueueChannel::create();
        $requestPayload = "some";

        $chainHandler = ChainMessageHandlerBuilder::create()
            ->chain(TransformerBuilder::createHeaderEnricher([
                "token" => "123"
            ]))
            ->withOutputMessageChannel($outputChannelName)
            ->build(InMemoryChannelResolver::createFromAssociativeArray([
                $outputChannelName => $outputChannel
            ]), InMemoryReferenceSearchService::createEmpty());

        $chainHandler->handle(
            MessageBuilder::withPayload($requestPayload)
                ->build()
        );

        $this->assertEquals(
            "123",
            $outputChannel->receive()->getHeaders()->get("token")
        );
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_chaining_with_three_levels()
    {
        $replyChannel = QueueChannel::create();
        $requestPayload = "x";

        $this->createChainHandlerAndHandle(
            [
                ChainMessageHandlerBuilder::create()
                    ->chain(
                        ChainMessageHandlerBuilder::create()
                            ->chain(EnricherBuilder::create([
                                EnrichHeaderWithExpressionBuilder::createWith("some", "false")
                            ]))
                            ->chain(EnricherBuilder::create([
                                EnrichHeaderWithExpressionBuilder::createWith("some2", "request['payload']")
                            ]))
                    )
            ],
            $requestPayload,
            $replyChannel
        );

        $message = $replyChannel->receive();
        $this->assertEquals(
            false,
            $message->getHeaders()->get("some")
        );
        $this->assertEquals(
            "x",
            $message->getHeaders()->get("some2")
        );
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_chaining_multiple_handlers()
    {
        $replyChannel = QueueChannel::create();
        $requestPayload = 1;

        $this->createChainHandlerAndHandle(
            [
                    ChainMessageHandlerBuilder::create()
                        //1
                        ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), "sum"))
                        //2
                        ->chain(
                            ChainMessageHandlerBuilder::create()
                                ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), "multiply"))
                                ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), "sum"))
                        )
                        //5
                        ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), "sum"))
                        //6
                        ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), "multiply"))
                        //12
                        ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), "sum")),
                        //13
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), "sum"),
                    //14
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), "multiply"),
                    //28
                    ChainMessageHandlerBuilder::create()
                        ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), "sum"))
                        //30
                        ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), "multiply")),
                        //60
                    ChainMessageHandlerBuilder::create()
                        ->chain(
                            ChainMessageHandlerBuilder::create()
                                ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), "multiply"))
                                //120
                                ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(10), "sum"))
                                //130
                        )
                        ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), "multiply")),
                        //260
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), "sum")
                    //261
            ],
            $requestPayload,
            $replyChannel
        );

        $message = $replyChannel->receive();
        $this->assertEquals(
            261,
            $message->getPayload()
        );
    }

    public function test_passing_references_objects_to_top_handler()
    {
        $chainBuilder = ChainMessageHandlerBuilder::create()
                        ->chain(TransformerBuilder::create("some", "method"));

        $this->assertEquals(["some"], $chainBuilder->getRequiredReferenceNames());
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \Exception
     */
    public function test_chaining_with_router_at_the_end()
    {
        $outputChannelName = "outputChannel";
        $outputChannel = QueueChannel::create();
        $chainBuilder = ChainMessageHandlerBuilder::create()
            ->chain(TransformerBuilder::createWithExpression( "1 + 1"))
            ->withOutputMessageHandler(RouterBuilder::createRecipientListRouter([$outputChannelName]))
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $outputChannelName => $outputChannel
                ]),
                InMemoryReferenceSearchService::createWith([ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()])
            );

        $chainBuilder->handle(MessageBuilder::withPayload("some1")->build());

        $this->assertEquals(
            2,
            $outputChannel->receive()->getPayload()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_throwing_exception_if_configured_output_channel_and_output_handler()
    {
        $this->expectException(InvalidArgumentException::class);

        ChainMessageHandlerBuilder::create()
            ->chain(TransformerBuilder::createWithExpression( "1 + 1"))
            ->withOutputMessageHandler(RouterBuilder::createRecipientListRouter(["some"]))
            ->withOutputMessageChannel("some")
            ->build(
                InMemoryChannelResolver::createEmpty(),
                InMemoryReferenceSearchService::createEmpty()
            );
    }

    /**
     * @param $messageHandlers
     * @param $requestPayload
     * @param $replyChannel
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function createChainHandlerAndHandle(array $messageHandlers, $requestPayload, $replyChannel): void
    {
        $chainHandler = ChainMessageHandlerBuilder::create();
        foreach ($messageHandlers as $messageHandler) {
            $chainHandler = $chainHandler->chain($messageHandler);
        }

        $chainHandler = $chainHandler
            ->build(InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ]));

        $chainHandler->handle(
            MessageBuilder::withPayload($requestPayload)
                ->setReplyChannel($replyChannel)
                ->build()
        );
    }

    public function test_converting_to_string()
    {
        $inputChannelName = 'inputChannel';
        $endpointName = "someName";

        $this->assertEquals(
            ChainMessageHandlerBuilder::create()
                ->withInputChannelName($inputChannelName)
                ->withEndpointId($endpointName),
            sprintf("Handler of type %s with name `%s` for input channel `%s`", ChainMessageHandlerBuilder::class, $endpointName, $inputChannelName)
        );
    }
}