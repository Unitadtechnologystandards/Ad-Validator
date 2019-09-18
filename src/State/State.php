<?php
declare(strict_types=1);

namespace OVKAC\State;

interface State
{
    public function load(): void;

    public function save(): void;

    public function flush(): void;

    public function data(): StateData;

    public function status(): StateStatus;
}