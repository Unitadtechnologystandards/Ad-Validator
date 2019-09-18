<?php
declare(strict_types=1);

namespace OVKAC\Api;

interface Files
{
    /** @throws \Exception */
    public function uploadAction(): void;

    /** @throws \Exception */
    public function ensureWorkDir(
        bool $forceNew = false
    ): string;

    /** @throws \Exception */
    public function deleteWorkDir(
        bool $unusedCheck = false
    ): void;

    /** @throws \Exception */
    public function deleteResultsWorkDir(): void;

    /** @throws \Exception */
    public function checkWorkDir(
        string $workDir
    ): void;

    /** @throws \Exception */
    public function create(
        string $workDir,
        string $name,
        string $data
    ): void;

    /** @throws \Exception */
    public function indexFile(): string;
}