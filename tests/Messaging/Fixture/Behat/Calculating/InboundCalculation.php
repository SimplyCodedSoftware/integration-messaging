<?php


namespace Test\SimplyCodedSoftware\Messaging\Fixture\Behat\Calculating;

use SimplyCodedSoftware\Messaging\Annotation\InboundChannelAdapter;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\Poller;
use SimplyCodedSoftware\Messaging\Annotation\ServiceActivator;

/**
 * Class InboundCalculation
 * @package Fixture\Behat\Calculating
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class InboundCalculation
{
    /**
     * @return int
     * @InboundChannelAdapter(
     *     endpointId="inboundCalculator",
     *     requestChannelName="calculateForInbound",
     *     poller=@Poller(
     *          handledMessageLimit=1
     *     )
     * )
     * @BeforeMultiplyCalculation(amount=3)
     * @AroundSumCalculation(amount=2)
     * @AfterMultiplyCalculation(amount=10)
     */
    public function calculateFor() : int
    {
        return 5;
    }

    /**
     * The result will be published to channel after this method
     *
     * @param int $number
     * @return int
     * @ServiceActivator(
     *     inputChannelName="calculateForInbound",
     *     outputChannelName="resultChannel"
     * )
     */
    public function calculate(int $number) : int
    {
        return $number;
    }
}