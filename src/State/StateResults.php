<?php
declare(strict_types=1);

namespace OVKAC\State;

use OVKAC\Formats\Format;

interface StateResults extends \JsonSerializable
{
    public function setWorkDir(string $workDir): void;

    public function workDir(): string;

    public function setFormat(?Format $format): void;

    public function setArchiveName(string $archiveName): void;

    public function create(): void;

    public function setProfiling(array $profiling): void;

    public function setPreviewUrl(string $preview): void;
}