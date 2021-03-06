<?php

declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Scheduling;

use Test\SimplyCodedSoftware\Messaging\Fixture\Scheduling\StubTaskExecutor;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Messaging\Scheduling\PeriodicTrigger;
use SimplyCodedSoftware\Messaging\Scheduling\StubUTCClock;
use SimplyCodedSoftware\Messaging\Scheduling\SyncTaskScheduler;

/**
 * Class SyncTaskSchedulerTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Scheduling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SyncTaskSchedulerTest extends TestCase
{
    public function test_when_first_time_called_then_it_should_trigger_immediately()
    {
        $clock = StubUTCClock::createWithCurrentTime("2016-01-01 12:00:00");
        $syncTaskExecutor = SyncTaskScheduler::createWithEmptyTriggerContext($clock);
        $taskExecutor = StubTaskExecutor::create();

        $syncTaskExecutor->schedule($taskExecutor, PeriodicTrigger::create(1, 0));

        $this->assertTrue($taskExecutor->wasCalled());
    }

    public function test_when_calling_second_time_it_should_wait_fixed_rate()
    {
        $clock = StubUTCClock::createWithCurrentTime("2016-01-01 12:00:00");
        $syncTaskExecutor = SyncTaskScheduler::createWithEmptyTriggerContext($clock);
        $taskExecutor = StubTaskExecutor::create();

        $trigger = PeriodicTrigger::create(1000, 0);
        $syncTaskExecutor->schedule($taskExecutor, $trigger);

        $this->assertEquals(1, $taskExecutor->getCalledTimes(), "Was called too many times");

        $clock->changeCurrentTime('2016-01-01 12:00:01');
        $syncTaskExecutor->schedule($taskExecutor, $trigger);

        $this->assertEquals(2, $taskExecutor->getCalledTimes());
    }
}