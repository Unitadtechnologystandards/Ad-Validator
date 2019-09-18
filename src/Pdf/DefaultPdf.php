<?php
declare(strict_types=1);

namespace OVKAC\Pdf;

use Error;
use OVKAC\Logger\Logger;
use OVKAC\State\State;

final class DefaultPdf implements Pdf
{
    private $state;
    private $logger;
    private $rootDir;
    private $host;
    private $chromiumBinary = '/usr/bin/chromium';

    public function __construct(
        string $host,
        string $rootDir,
        State $state,
        Logger $logger
    )
    {
        $this->host = $host;
        $this->state = $state;
        $this->rootDir = $rootDir;
        $this->logger = $logger;
    }

    public function create(): void
    {
        $this->logger->debug('Create pdf');

        $workDir = $this->state->data()->parameters()->workDir();
        if (strlen($workDir) > 0) {

            $absWorkDir = sprintf(
                '%s/public/workdir/%s',
                $this->rootDir,
                $workDir
            );
            if (!is_dir($absWorkDir)) {
                mkdir($absWorkDir, 0777, true);
            }
            $absWorkDir = realpath($absWorkDir);

            $filePath = sprintf(
                '%s/ovkAdValidator.html',
                $absWorkDir
            );

            $outFile = sprintf(
                '%s/ovkAdValidator.pdf',
                $absWorkDir
            );

            $this->logger->debug(
                sprintf(
                    'Create pdf: %s > %s',
                    $filePath,
                    $outFile
                )
            );

            // --------------

            $results = $this->state->data()->results();
            $html = str_replace(
                '/* PDF-DATA */',
                sprintf(
                    'window.ovkadcheckPdfData = %s;',
                    json_encode($results, JSON_PRETTY_PRINT)
                ),
                str_replace(
                    '<!-- BASE -->',
                    '<base href="/">',
                    file_get_contents($this->rootDir . '/public/index.html')
                )
            );

            $success = file_put_contents(
                $filePath,
                $html
            );
            if ($success === false) {
                throw new \Error('Write error');
            }

            // -----

            $command = sprintf(
                'timeout 30 %s --no-sandbox --virtual-time-budget=10000 --headless --disable-gpu --run-all-compositor-stages-before-draw --print-to-pdf="%s" %s/workdir/%s/ovkadcheckPdf.html',
                $this->chromiumBinary,
                $outFile,
                $this->host, // TODO
                $workDir
            );
            $this->logger->info(
                sprintf(
                    'Pdf: %s',
                    $command
                )
            );

            // TODO error handling
            `$command`;

            header('Content-type: application/pdf');
            header('Content-Disposition: inline; filename="AdValidator.pdf"');
            readfile($outFile);
        }
        else {
            throw new Error('No data');
        }

        $this->logger->debug('----------------------');
    }

}