<?php

namespace Charcoal\Communicator;

use RuntimeException;

// From 'charcoal-contrib-communicator'
use Charcoal\Communicator\CommunicatorInterface;

/**
 * The Communicator Aware Trait provides the methods necessary for an object
 * to use a "Communicator" service.
 */
trait CommunicatorAwareTrait
{
    /**
     * The Communicator service.
     *
     * @var CommunicatorInterface
     */
    private $communicator;

    /**
     * Set the communicator service.
     *
     * @param  CommunicatorInterface $communicator The Communicator service.
     * @return void
     */
    public function communicator()
    {
        if (!isset($this->communicator)) {
            throw new RuntimeException('Communicator has not been set on this object.');
        }

        return $this->communicator;
    }

    /**
     * Set the communicator service.
     *
     * @param  CommunicatorInterface $communicator The Communicator service.
     * @return void
     */
    public function setCommunicator(CommunicatorInterface $communicator)
    {
        $this->communicator = $communicator;

        return $this;
    }
}
