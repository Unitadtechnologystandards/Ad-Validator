<?php
declare(strict_types=1);

namespace OVKAC\State;

interface StateData extends \JsonSerializable
{
    public function parameters(): StateParameters;

    public function results(): StateResults;
}