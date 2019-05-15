<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceInterface;

/**
 * Interface ServiceInterface
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ServiceInterfaceSendOnly
{
    public function sendMail(string $content) : void;

    /**
     * @param string $content
     * @param array  $metadata
     */
    public function sendMailWithMetadata(string $content, array $metadata) : void;
}