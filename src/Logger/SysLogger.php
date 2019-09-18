<?php
declare(strict_types=1);

namespace OVKAC\Logger;

final class SysLogger implements Logger
{
    private $destination;
    private $session;

    public function __construct(
        string $destination,
        string $session
    )
    {
        $this->destination = $destination;
        $this->session = $session;
    }

    public function debug(string $message, array $context = []): void
    {
        $this->_log(LogLevel::DEBUG, $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->_log(LogLevel::INFO, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->_log(LogLevel::ERROR, $message, $context);
    }

    public function log(
        string $level,
        string $message,
        array $context = []
    ): void
    {
        $this->_log($level, $message, $context);
    }

    private function _log(
        string $level,
        string $message,
        array $context = []
    ): void
    {
        error_log(
            sprintf(
                "[%s] %s: [%s] %s\n",
                $this->session,
                date('Y-m-d H:i:s'),
                $level,
                $message
            ),
            3,
            $this->destination
        );
    }

}