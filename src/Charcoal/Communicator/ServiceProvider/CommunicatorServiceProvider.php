<?php

namespace Charcoal\Communicator\ServiceProvider;

use Charcoal\Communicator\Communicator;
use Charcoal\Communicator\CommunicatorInterface;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Provides the default communicator service.
 */
class CommunicatorServiceProvider implements ServiceProviderInterface
{
    /**
     * @param  Container $container The service container.
     * @return void
     */
    public function register(Container $container)
    {
        /**
         * Instance of the Communicator, that is used for email communications.
         *
         * @param  Container $container The service container.
         * @return CommunicatorInterface
         */
        $container['communicator'] = function (Container $container) {
            $config = $container['config']->get('communicator');

            $communicator = new Communicator([
                'config'       => $container['config'],
                'emailFactory' => $container['email/factory'],
                'translator'   => $container['translator'],
                'view'         => $container['view'],
            ]);

            if (isset($config['channels']) && is_array($config['channels'])) {
                foreach ($config['channels'] as $ident => $channel) {
                    $communicator->addChannel($ident, $channel);
                }
            }

            return $communicator;
        };
    }
}
