<?php

namespace Charcoal\Communicator\ServiceProvider;

use Charcoal\Communicator\Communicator;
use Charcoal\Communicator\CommunicatorInterface;
use DI\Container;
use Psr\Container\ContainerInterface;

/**
 * Provides the default communicator service.
 */
class CommunicatorServiceProvider
{
    /**
     * @param  Container $container The service container.
     * @return void
     */
    public function register(ContainerInterface $container)
    {
        /**
         * Instance of the Communicator, that is used for email communications.
         *
         * @param  Container $container The service container.
         * @return CommunicatorInterface
         */
        $container->set('communicator', function (ContainerInterface $container) {
            $appConfig = $container->get('config');
            $comConfig = $appConfig['communicator'];

            $communicator = new Communicator([
                'config'       => $appConfig,
                'emailFactory' => $container->get('email/factory'),
                'translator'   => $container->get('translator'),
                'view'         => $container->get('view'),
            ]);

            if (isset($comConfig['channels']) && is_array($comConfig['channels'])) {
                $communicator->addChannels($comConfig['channels']);
            }

            return $communicator;
        });
    }
}
