<?php

namespace SimplyCodedSoftware\DomainModel\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;
use SimplyCodedSoftware\Messaging\Annotation\EndpointAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\InputOutputEndpointAnnotation;

/**
 * Class EventHandler
 * @package SimplyCodedSoftware\DomainModel\Annotation
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"METHOD"})
 */
class EventHandler extends EndpointAnnotation
{
    /**
     * @var array
     */
    public $parameterConverters = [];
    /**
     * If handler has no need in message payload, you can add name of the class name in annotation
     *
     * @var string
     */
    public $messageClassName;
    /**
     * @var bool
     */
    public $filterOutOnNotFound = false;
    /**
     * Redirect to channel when factory method found already existing aggregate
     *
     * @var string
     */
    public $redirectToOnAlreadyExists = "";
    /**
     * @var string
     */
    public $outputChannelName = '';
    /**
     * Required interceptor reference names
     *
     * @var array
     */
    public $requiredInterceptorNames = [];
}