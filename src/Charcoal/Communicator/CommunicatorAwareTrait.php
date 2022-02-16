<?php

declare(strict_types=1);

namespace Charcoal\Communicator;

use Charcoal\Communicator\CommunicatorInterface;
use RuntimeException;

/**
 * Basic implementation of CommunicatorAwareInterface.
 */
trait CommunicatorAwareTrait
{
    /**
     * The Communicator service.
     *
     * @var CommunicatorInterface|null
     */
    private $communicator;

    /**
     * Gets a communicator instance on the object.
     *
     * @return CommunicatorInterface
     */
    public function getCommunicator(): CommunicatorInterface
    {
        return $this->communicator;
    }

    /**
     * Sets a communicator instance on the object.
     *
     * @param  CommunicatorInterface $communicator The communicator service.
     * @return void
     */
    public function setCommunicator(CommunicatorInterface $communicator): void
    {
        $this->communicator = $communicator;
    }
}
