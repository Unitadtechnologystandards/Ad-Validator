<?php
declare(strict_types=1);

namespace OVKAC\Api;

use FilesystemIterator;
use OVKAC\Logger\Logger;
use OVKAC\State\State;

final class DefaultFiles implements Files
{
    private $logger;
    private $state;
    private $request;
    private $workDir;

    public function __construct(
        Logger $logger,
        Request $request,
        State $state,
        string $workDir
    )
    {
        $this->logger = $logger;
        $this->request = $request;
        $this->state = $state;
        $this->workDir = $workDir;
    }

    public function uploadAction(): void
    {
        $file = (string)$this->request->got('file', '');
        if (strlen($file) > 0) {
            $raw = $this->request->rawInput();
            $size = strlen($raw);
            $name = $file;

            if ($size === 0) {
                $this->state->status()->setError(
                    'Upload failed'
                );
                return;
            }
            elseif ($size > 10000000) {
                $this->state->status()->setError(
                    'Upload max size exceeded'
                );
                return;
            }

            $this->logger->debug(sprintf(
                'Got %s (%d)',
                $name,
                $size
            ));

            $this->deleteWorkDir(true);
            $this->ensureWorkDir(true);

            try {
                $this->receiveUpload($raw);
            }
            catch (\Exception $e) {
                $this->state->status()->setError(
                    'Fehler: ' . $e->getMessage()
                );
                return;
            }

            $parameters = $this->state->data()->parameters();
            $parameters->setArchiveName($name);
        }
    }

    public function ensureWorkDir(
        bool $forceNew = false
    ): string
    {
        $parameters = $this->state->data()->parameters();
        $workDir = $parameters->workDir();
        if (strlen($workDir) === 0 || $forceNew) {
            $workDir = md5(microtime());
            $path = $this->workDir($workDir);
            if (file_exists($path)) {
                throw new \Exception('WorkDir collision!');
            }
            mkdir($path, 0777, true);
            $this->requireWorkDir($workDir);
            $parameters->setWorkDir($workDir);
            $this->logger->debug(
                sprintf('WorkDir %s created', $workDir)
            );
        }
        else {
            $this->requireWorkDir($workDir);
        }
        return $workDir;
    }

    public function deleteWorkDir(
        bool $unusedCheck = false
    ): void
    {
        $parameters = $this->state->data()->parameters();
        $workDir = $parameters->workDir();
        if (strlen($workDir) > 0) {
            if ($unusedCheck) {
                $results = $this->state->data()->results();
                $workDirResults = $results->workDir();
                if (
                    strlen($workDirResults) > 0
                    && $workDirResults === $workDir
                ) {
                    return;
                }
            }
            $this->rmRecursive($this->workDir($workDir));
            $this->logger->debug(
                sprintf('WorkDir (parameters) %s removed', $workDir)
            );
            $parameters->setWorkDir('');
        }
    }

    public function deleteResultsWorkDir(): void
    {
        $results = $this->state->data()->results();
        $resultsWorkDir = $results->workDir();
        $workDir = $this->state->data()->parameters()->workDir();
        if (
            strlen($resultsWorkDir) > 0
            && $resultsWorkDir !== $workDir
        ) {
            $this->rmRecursive($this->workDir($resultsWorkDir));
            $this->logger->debug(
                sprintf(
                    'WorkDir (results) %s removed',
                    $resultsWorkDir
                )
            );
            $results->setWorkDir('');
        }
    }

    public function checkWorkDir(
        string $workDir
    ): void
    {
        if (
            strlen($workDir) === 0
            || !is_dir($this->workDir($workDir))
        ) {
            throw new \Exception('WorkDir error!');
        }
    }

    public function create(
        string $workDir,
        string $name,
        string $data
    ): void
    {
        $this->checkWorkDir($workDir);

        $file = sprintf(
            '%s/%s',
            $this->workDir($workDir),
            $name
        );

        file_put_contents(
            $file,
            $data
        );

        if (
            !file_exists($file)
        ) {
            throw new \Exception('Create file failed');
        }
    }

    public function indexFile(): string
    {
        $parameters = $this->state->data()->parameters();
        $workDir = $parameters->workDir();

        $indexFile = sprintf(
            '%s/index.html',
            $this->workDir($workDir)
        );

        if (!file_exists(
            $indexFile
        )) {
            throw new \Exception('Missing index.html?');
        }

        return sprintf(
            '%s/index.html',
            $workDir
        );
    }

    /** @throws \Exception */
    private function receiveUpload(string $raw): void
    {
        $this->logger->debug(
            sprintf(
                'Receive upload size: %d',
                strlen($raw)
            )
        );

        $parameters = $this->state->data()->parameters();
        $workDir = $parameters->workDir();

        $filename = sprintf(
            '%s/%s.zip',
            $this->workDir($workDir),
            md5(microtime())
        );

        $status = file_put_contents($filename, $raw);
        if ($status === false) {
            throw new \Exception('Writing upload failed (1)');
        }
        if (!file_exists($filename)) {
            throw new \Exception('Writing upload failed (2)');
        }

        $zip = new \ZipArchive;
        $opened = $zip->open($filename);
        if ($opened === true) {
            $extracted = $zip->extractTo(
                $this->workDir($workDir)
            );
            $zip->close();
            if ($extracted === true) {
                unlink($filename);

                $amount = iterator_count(new FilesystemIterator(
                    $this->workDir($workDir),
                    FilesystemIterator::SKIP_DOTS
                ));
                if ($amount === 0) {
                    throw new \Exception(
                        'Fehlerhaftes Archiv (1)'
                    );
                }

                if (!file_exists(
                    sprintf(
                        '%s/index.html',
                        $this->workDir($workDir)
                    )
                )) {
                    throw new \Exception('Keine index.html?');
                }

                $successMessage = sprintf(
                    'Archiv gespeichert und entpackt (%d Dateien)',
                    (int)$amount
                );
                $this->state->status()->setInfo($successMessage);
                $this->logger->debug($successMessage);
            }
            else {
                throw new \Exception(
                    'Fehlerhaftes Archiv (2)'
                );
            }
        }
        else {
            throw new \Exception(
                'Fehlerhaftes Archiv (3)'
            );
        }
    }

    /** @throws \Exception */
    private function requireWorkDir(string $workDir): void
    {
        if (strlen($workDir) > 0) {
            $path = $this->workDir($workDir);
            if (!file_exists($path) || !is_dir($path)) {
                throw new \Exception(
                    sprintf('WorkDir %s missing!', $workDir)
                );
            }
            $this->logger->debug(
                sprintf(
                    'WorkDir %s exists',
                    $workDir
                )
            );
        }
        else {
            throw new \Exception('No workDir??');
        }
    }

    /** @throws \Exception */
    private function workDir(string $workDir): string
    {
        if (strlen($workDir) === 0) {
            throw new \Exception('No workDir??');
        }
        return $this->workDir . '/' . $workDir;
    }

    private function rmRecursive($dir)
    {
        if (false === file_exists($dir)) {
            return false;
        }
        /** @var \SplFileInfo[] $files */
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $dir,
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                if (false === rmdir($fileinfo->getRealPath())) {
                    return false;
                }
            }
            else {
                if (false === unlink($fileinfo->getRealPath())) {
                    return false;
                }
            }
        }
        return rmdir($dir);
    }
}