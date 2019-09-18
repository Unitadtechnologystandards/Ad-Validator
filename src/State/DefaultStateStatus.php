<?php
declare(strict_types=1);

namespace OVKAC\State;

final class DefaultStateStatus implements StateStatus
{
    private $info = '';
    private $error = '';
    private $warning = '';

    public function info(): string
    {
        return $this->info;
    }

    public function setInfo(string $info): void
    {
        $this->info = $info;
    }

    public function error(): string
    {
        return $this->error;
    }

    public function setError(string $error): void
    {
        $this->error = $error;
    }

    public function warning(): string
    {
        return $this->warning;
    }

    public function setWarning(string $warning): void
    {
        $this->warning = $warning;
    }

    public function jsonSerialize()
    {
        return [
            'info' => $this->info,
            'warning' => $this->warning,
            'error' => $this->error,
        ];
    }

}