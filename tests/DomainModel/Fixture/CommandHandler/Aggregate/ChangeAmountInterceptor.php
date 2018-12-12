<?php

namespace Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate;

/**
 * Class AddAmountInterceptor
 * @package Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ChangeAmountInterceptor
{
    /**
     * @var int
     */
    private $newAmount;

    /**
     * ChangeAmountInterceptor constructor.
     *
     * @param int $newAmount
     */
    private function __construct(int $newAmount)
    {
        $this->newAmount = $newAmount;
    }

    /**
     * @param int $newAmount
     *
     * @return ChangeAmountInterceptor
     */
    public static function create(int $newAmount) : self
    {
        return new self($newAmount);
    }

    /**
     * @return int
     */
    public function change() : int
    {
        return $this->newAmount;
    }
}