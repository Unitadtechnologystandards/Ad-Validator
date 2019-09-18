<?php
declare(strict_types=1);

namespace OVKAC\State;

interface StateStatus extends \JsonSerializable
{
    public function info(): string;
    public function warning(): string;
    public function error(): string;
    public function setInfo(string $info): void;
    public function setWarning(string $info): void;
    public function setError(string $info): void;

}