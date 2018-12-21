<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Annotation\Interceptor;

/**
 * Class InterceptedEndpointAnnotation
 * @package SimplyCodedSoftware\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class MethodInterceptors
{
    /**
     * List of method level pre interceptors
     *
     * @var array
     */
    public $preCallInterceptors = [];
    /**
     * List of method post interceptors
     *
     * @var array
     */
    public $postCallInterceptors = [];
    /**
     * @var array used in class level interceptors
     */
    public $excludedMethods = [];
}