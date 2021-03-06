<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor;

use SimplyCodedSoftware\Messaging\Annotation\Interceptor\Around;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\MethodInterceptor;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

/**
 * Class CallWithReplacingArgumentsInterceptorExample
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MethodInterceptor()
 */
class CallWithReplacingArgumentsInterceptorExample extends BaseInterceptorExample
{
    /**
     * @param MethodInvocation $methodInvocation
     * @return mixed
     * @Around()
     */
    public function callWithReplacingArguments(MethodInvocation $methodInvocation)
    {
        foreach ($this->argumentsToReplace as $parameterName => $value) {
            $methodInvocation->replaceArgument($parameterName, $value);
        }

        return $methodInvocation->proceed();
    }
}