<?php

namespace SimplyCodedSoftware\Http\FileSystemMover;
use Psr\Http\Message\UploadedFileInterface;
use Ramsey\Uuid\Uuid;

/**
 * Class UuidFileNameGenerator
 * @package SimplyCodedSoftware\Http\FileSystemMover
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class UuidFileNameGenerator implements FileNameGenerator
{
    /**
     * @inheritDoc
     */
    public function generateFor(UploadedFileInterface $uploadedFile): string
    {
        return Uuid::uuid4()->toString();
    }
}