<?php
declare(strict_types=1);


namespace SimplyCodedSoftware\Messaging\Endpoint\Interceptor;

use SimplyCodedSoftware\Messaging\Config\ConfigurationException;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerInterceptor;

/**
 * Class LimitMemoryUsageInterceptor
 * @package SimplyCodedSoftware\Messaging\Endpoint\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LimitMemoryUsageInterceptor implements ConsumerInterceptor
{
    /**
     * @var int
     */
    private $memoryLimitInMegaBytes;

    /**
     * LimitMemoryUsageInterceptor constructor.
     * @param int $memoryLimitInMegaBytes
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function __construct(int $memoryLimitInMegaBytes)
    {
        if ($memoryLimitInMegaBytes < 0) {
            throw ConfigurationException::create("Memory limit usage is set to incorrect value: {$memoryLimitInMegaBytes}");
        }

        $this->memoryLimitInMegaBytes = $memoryLimitInMegaBytes * 1024 * 1024;
    }

    /**
     * @inheritDoc
     */
    public function onStartup(): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function shouldBeStopped(): bool
    {
        if ($this->memoryLimitInMegaBytes === 0) {
            return false;
        }

        return memory_get_usage(true) >= $this->memoryLimitInMegaBytes;
    }

    /**
     * @inheritDoc
     */
    public function preRun(): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function postRun(): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function postSend(): void
    {
        return;
    }
}