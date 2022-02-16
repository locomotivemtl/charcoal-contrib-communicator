<?php

declare(strict_types=1);

namespace Charcoal\Communicator;

use Charcoal\Communicator\CommunicatorInterface;

/**
 * Describes a communicator-aware instance.
 */
interface CommunicatorAwareInterface
{
    /**
     * Gets a communicator instance on the object.
     *
     * @return CommunicatorInterface
     */
    public function getCommunicator(): CommunicatorInterface;

    /**
     * Sets a communicator instance on the object.
     *
     * @param  CommunicatorInterface $communicator The communicator service.
     * @return void
     */
    public function setCommunicator(CommunicatorInterface $communicator): void;
}
