<?php

namespace Charcoal\Communicator\ServiceProvider;

// From Pimple
use Pimple\Container;
use Pimple\ServiceProviderInterface;

// From 'charcoal-contrib-communicator'
use Charcoal\Communicator\Communicator;

/**
 * Communicator Service Provider.
 *
 * ## Container dependencies
 *
 * The following keys are expected to be set on the container
 * (from external sources / providers):
 *
 * - `config` A charcoal app config (\Charcoal\Config\ConfigInterface)
 * - `database` A PDO database instance
 * - `logger` A PSR-3 compliant logger.
 * - `view` A \Charcoal\View\ViewInterface instance
 *
 * ## Services
 *
 * The following services are registered on the container:
 *
 * - `model/factory` A \Charcoal\Factory\FactoryInterface factory to create models.
 * - `model/collection/loader` A collection loader (should not be used).
 */
class CommunicatorServiceProvider implements ServiceProviderInterface
{
    /**
     * @param  Container $container Pimple DI Container.
     * @return void
     */
    public function register(Container $container)
    {
        /**
         * Instance of the Communicator, that is used for email communications.
         *
         * @param  Container $container Pimple DI container.
         * @return Communicator
         */
        $container['communicator'] = function (Container $container) {
            $config = $container['config']->get('communicator');

            $communicator = new Communicator([
                'config'       => $container['config'],
                'emailFactory' => $container['email/factory'],
                'translator'   => $container['translator'],
                'view'         => $container['view'],
            ]);
            if (!empty($config) && is_array($config) && isset($config['channels']) && is_array($config['channels'])) {
                foreach ($config['channels'] as $ident => $channel) {
                    $communicator->addChannel($ident, $channel);
                }
            }

            return $communicator;
        };
    }
}
