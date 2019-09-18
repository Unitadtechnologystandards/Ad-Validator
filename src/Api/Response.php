<?php
declare(strict_types=1);

namespace OVKAC\Api;

use OVKAC\Formats\Formats;
use OVKAC\State\State;

interface Response
{
    public function badRequest(
        string $string
    );

    public function render(
        State $state,
        Formats $formats
    ): void;
}