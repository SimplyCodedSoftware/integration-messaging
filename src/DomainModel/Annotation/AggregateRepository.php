<?php
declare(strict_types=1);


namespace SimplyCodedSoftware\DomainModel\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class AggregateRepository
 * @package SimplyCodedSoftware\DomainModel\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"CLASS"})
 */
class AggregateRepository
{
    /**
     * If not configured it will take class name as reference
     *
     * @var string
     */
    public $referenceName;
}