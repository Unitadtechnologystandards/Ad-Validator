<?php
declare(strict_types=1);

namespace OVKAC\Api;

interface Api
{
    /** @throws \Exception */
    public function execute(): void;
}