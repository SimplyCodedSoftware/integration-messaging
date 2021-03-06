<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Interceptor;

use SimplyCodedSoftware\Messaging\Annotation\Interceptor\After;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\Before;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\MethodInterceptor;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\ServiceActivatorInterceptor;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Header;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Payload;

/**
 * Class ServiceActivatorInterceptorExample
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MethodInterceptor(referenceName="someMethodInterceptor")
 */
class ServiceActivatorInterceptorExample
{
    /**
     * @Before(precedence=2, pointcut=ServiceActivatorInterceptorExample::class, parameterConverters={
     *      @Payload(parameterName="name"),
     *      @Header(parameterName="surname", headerName="surname")
     * })
     * @param string $name
     * @param string $surname
     */
    public function doSomethingBefore(string $name, string $surname) : void
    {

    }

    /**
     * @After(parameterConverters={
     *      @Payload(parameterName="name"),
     *      @Header(parameterName="surname", headerName="surname")
     * })
     * @param string $name
     * @param string $surname
     */
    public function doSomethingAfter(string $name, string $surname) : void
    {

    }
}