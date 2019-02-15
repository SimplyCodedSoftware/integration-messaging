<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Amqp;

use Interop\Amqp\Impl\AmqpBind as EnqueueBinding;

/**
 * Class AmqpBinding
 * @package SimplyCodedSoftware\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpBinding
{
    /**
     * @var EnqueueBinding
     */
    private $enqueueBinding;
    /**
     * @var string
     */
    private $queueName;
    /**
     * @var string
     */
    private $exchangeName;

    /**
     * AmqpBinding constructor.
     * @param AmqpExchange $amqpExchange
     * @param AmqpQueue $amqpQueue
     * @param string|null $routingKey
     */
    private function __construct(AmqpExchange $amqpExchange, AmqpQueue $amqpQueue, ?string $routingKey)
    {
        $this->queueName = $amqpQueue->getQueueName();
        $this->exchangeName = $amqpExchange->getExchangeName();
        $this->enqueueBinding = new EnqueueBinding($amqpExchange->toEnqueueExchange(), $amqpQueue->toEnqueueQueue(), $routingKey);
    }

    /**
     * @return string
     */
    public function getQueueName() : string
    {
        return $this->queueName;
    }

    /**
     * @return string
     */
    public function getExchangeName() : string
    {
        return $this->exchangeName;
    }

    /**
     * @return bool
     */
    public function isBindToDefaultExchange() : bool
    {
        return $this->exchangeName === "";
    }

    /**
     * @param string $amqpQueueName
     * @return bool
     */
    public function isRelatedToQueueName(string $amqpQueueName) : bool
    {
        return $this->queueName == $amqpQueueName;
    }

    /**
     * @param string $amqpExchangeName
     * @return bool
     */
    public function isRelatedToExchangeName(string $amqpExchangeName) : bool
    {
        return $this->exchangeName == $amqpExchangeName;
    }

    /**
     * @return EnqueueBinding
     */
    public function toEnqueueBinding() : EnqueueBinding
    {
        return $this->enqueueBinding;
    }

    /**
     * @param AmqpExchange $amqpExchange
     * @param AmqpQueue $amqpQueue
     * @param string|null $routingKey
     * @return AmqpBinding
     */
    public static function createWith(AmqpExchange $amqpExchange, AmqpQueue $amqpQueue, ?string $routingKey) : self
    {
        return new self($amqpExchange, $amqpQueue, $routingKey);
    }

    /**
     * @param AmqpQueue $amqpQueue
     * @return AmqpBinding
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createForDefaultExchange(AmqpQueue $amqpQueue) : self
    {
        return new self(AmqpExchange::createDirectExchange(""), $amqpQueue, $amqpQueue->getQueueName());
    }

    /**
     * @param string $amqpExchangeName
     * @param string $amqpQueueName
     * @param string|null $routingKey
     * @return AmqpBinding
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createFromNames(string $amqpExchangeName, string $amqpQueueName, ?string $routingKey) : self
    {
        return new self(AmqpExchange::createDirectExchange($amqpExchangeName), AmqpQueue::createWith($amqpQueueName), $routingKey);
    }

    /**
     * @param string $amqpExchangeName
     * @param string $amqpQueueName
     *
     * @return AmqpBinding
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createFromNamesWithoutRoutingKey(string $amqpExchangeName, string $amqpQueueName) : self
    {
        return new self(AmqpExchange::createDirectExchange($amqpExchangeName), AmqpQueue::createWith($amqpQueueName), null);
    }
}