<?php
/**
 * Created by PhpStorm.
 * User: dgafka
 * Date: 05.04.18
 * Time: 09:50
 */

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Transformer;


class StdClassTransformer
{
    public function transform() : \stdClass
    {
        return new \stdClass();
    }
}