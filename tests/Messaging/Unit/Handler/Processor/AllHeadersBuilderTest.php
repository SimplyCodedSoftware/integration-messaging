<?php


namespace Test\SimplyCodedSoftware\Messaging\Unit\Handler\Processor;

use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\InterfaceParameter;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\CallableService;

/**
 * Class AllHeadersBuilderTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Handler\Processor
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AllHeadersBuilderTest extends TestCase
{
    public function test_retrieving_all_headers()
    {
        $result = AllHeadersBuilder::createWith("some")->build(InMemoryReferenceSearchService::createEmpty())->getArgumentFrom(
            InterfaceToCall::create(CallableService::class, "wasCalled"),
            InterfaceParameter::createNullable("some", TypeDescriptor::createStringType()),
            MessageBuilder::withPayload("some")
                ->setHeader("someId", 123)
                ->build(),
            []
        );
        unset($result[MessageHeaders::MESSAGE_ID]);
        unset($result[MessageHeaders::TIMESTAMP]);

        $this->assertEquals(
            [
                "someId" => 123
            ],
            $result
        );
    }
}