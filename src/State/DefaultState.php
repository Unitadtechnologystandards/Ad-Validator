<?php
declare(strict_types=1);

namespace OVKAC\State;

use OVKAC\Logger\Logger;

final class DefaultState implements State
{
    /** @var StateData */
    private $data;
    private $logger;
    private $status;

    public function __construct(
        Logger $logger
    )
    {
        $this->logger = $logger;
        $this->_reset();
    }

    public function load(): void
    {
        $this->logger->debug('Load state');

        session_start();

        $data = $_SESSION['state'] ?? null;
        if ($data instanceof StateData) {
            $this->data = $data;
        }
    }

    public function save(): void
    {
        $this->_save();
    }

    private function _save(): void
    {
        $this->logger->debug('Save state');
        $this->logger->debug('----------------------');

        $_SESSION['state'] = $this->data;
    }

    public function flush(): void
    {
        $this->logger->debug('Flush state');

        $this->_reset();
    }

    public function data(): StateData
    {
        return $this->data;
    }

    public function status(): StateStatus
    {
        return $this->status;
    }

    private function _reset(): void
    {
        $this->data = new DefaultStateData;
        $this->status = new DefaultStateStatus;
    }

}