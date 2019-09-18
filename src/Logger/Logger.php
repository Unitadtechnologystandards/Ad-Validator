<?php
declare(strict_types=1);

namespace OVKAC\Logger;

interface Logger
{
    public function debug(string $message, array $context = []): void;

    public function info(string $message, array $context = []): void;

    public function error(string $message, array $context = []): void;

    public function log(
        string $level,
        string $message,
        array $context = []
    ): void;
}