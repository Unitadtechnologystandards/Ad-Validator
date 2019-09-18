<?php
declare(strict_types=1);

namespace OVKAC\Checker;

interface Checker
{
    /** @throws \Exception */
    public function check(): void;
}