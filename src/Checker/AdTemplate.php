<?php
declare(strict_types=1);

namespace OVKAC\Checker;

interface AdTemplate
{
    /** @throws \Exception */
    public function create(): void;

    /** @throws \Exception */
    public function preview(): string;
}