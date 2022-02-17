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
    public function communicator()
    {
        if (!isset($this->communicator)) {
            throw new RuntimeException('Communicator has not been set on this object.');
        }

        return $this->communicator;
    }

    /**
     * Sets a communicator instance on the object.
     *
     * @param  CommunicatorInterface $communicator The communicator service.
     * @return void
     */
    public function setCommunicator(CommunicatorInterface $communicator)
    {
        $this->communicator = $communicator;

        return $this;
    }
}
