<?php

declare(strict_types=1);

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
            $appConfig = $container['config'];
            $comConfig = $appConfig['communicator'];

            $communicator = new Communicator([
                'emailFactory' => $container['email/factory'],
                'translator'   => $container['translator'],
                'view'         => $container['view'],
            ]);

            if (isset($comConfig['default_from'])) {
                $communicator->setDefaultFrom($comConfig['default_from']);
            } elseif (isset($appConfig['email.default_from'])) {
                $communicator->setDefaultFrom($appConfig['email.default_from']);
            }

            if (isset($comConfig['default_to'])) {
                $communicator->setDefaultTo($comConfig['default_to']);
            } elseif (isset($appConfig['email.default_to'])) {
                $communicator->setDefaultTo($appConfig['email.default_to']);
            }

            if (isset($comConfig['channels']) && is_array($comConfig['channels'])) {
                $communicator->addChannels($comConfig['channels']);
            }

            return $communicator;
        };
    }
}
