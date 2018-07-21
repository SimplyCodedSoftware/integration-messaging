<?php
/**
 * Created by PhpStorm.
 * User: dgafka
 * Date: 01.01.18
 * Time: 21:31
 */

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

use SimplyCodedSoftware\IntegrationMessaging\Channel\ChannelInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Channel\ChannelInterceptorBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\ChannelAdapterConsumerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\MessageHandlerConsumerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;


/**
 * Class MessagingSystemConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Configuration
{
    /**
     * @param MessageChannelBuilder $messageChannelBuilder
     * @return MessagingSystemConfiguration
     */
    public function registerMessageChannel(MessageChannelBuilder $messageChannelBuilder): MessagingSystemConfiguration;

    /**
     * @param MessageHandlerBuilder $messageHandlerBuilder
     * @return MessagingSystemConfiguration
     */
    public function registerMessageHandler(MessageHandlerBuilder $messageHandlerBuilder): MessagingSystemConfiguration;

    /**
     * @param ChannelInterceptorBuilder $channelInterceptorBuilder
     * @return MessagingSystemConfiguration
     */
    public function registerChannelInterceptor(ChannelInterceptorBuilder $channelInterceptorBuilder) : MessagingSystemConfiguration;

    /**
     * @param ChannelAdapterConsumerBuilder $consumerBuilder
     *
     * @return MessagingSystemConfiguration
     */
    public function registerConsumer(ChannelAdapterConsumerBuilder $consumerBuilder) : MessagingSystemConfiguration;

    /**
     * @param MessageHandlerConsumerBuilder $consumerFactory
     * @return MessagingSystemConfiguration
     */
    public function registerConsumerFactory(MessageHandlerConsumerBuilder $consumerFactory) : MessagingSystemConfiguration;

    /**
     * @param GatewayBuilder $gatewayBuilder
     * @return MessagingSystemConfiguration
     */
    public function registerGatewayBuilder(GatewayBuilder $gatewayBuilder) : MessagingSystemConfiguration;
}