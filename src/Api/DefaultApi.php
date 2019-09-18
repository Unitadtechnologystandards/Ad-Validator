<?php
declare(strict_types=1);

namespace OVKAC\Api;

use Error;
use OVKAC\Checker\DefaultChecker;
use OVKAC\Formats\DefaultFormats;
use OVKAC\Formats\Formats;
use OVKAC\Logger\Logger;
use OVKAC\State\DefaultState;
use OVKAC\State\State;

final class DefaultApi implements Api
{
    private $logger;
    private $request;
    private $response;

    /** @var State */
    private $state;

    /** @var ?Formats */
    private $formats;

    /** @var Files */
    private $files;

    private $rootDir;

    public function __construct(
        string $rootDir,
        Logger $logger,
        Request $request,
        Response $response
    )
    {
        $this->rootDir = $rootDir;
        $this->logger = $logger;
        $this->response = $response;
        $this->request = $request;

        $this->state = new DefaultState(
            $this->logger
        );
    }

    public function execute(): void
    {
        try {
            $this->_execute();
        }
        catch (\Exception $e) {
            $this->logger->error('Exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /** @throws \Exception */
    private function _execute(): void
    {
        $this->logger->debug('Started');

        $this->files = new DefaultFiles(
            $this->logger,
            $this->request,
            $this->state,
            '../workdir'
        );

        $this->state->load();

        $this->formats();

        $this->flushAction();
        $this->checkAction();
        $this->files->uploadAction();
        $this->formAction();

        $this->response->render(
            $this->state,
            $this->formats
        );

        $this->state->save();
    }

    /** @throws \Exception */
    private function formats(): Formats
    {
        if ($this->formats === null) { // heap
            $this->formats = (new DefaultFormats(
                $this->logger,
                sprintf(
                    '%s/var/cache/formats.json',
                    $this->rootDir
                ),
                'https://somewhere/formats.json' // TODO
            ))->load();
        }

        return $this->formats;
    }

    private function warnUrl(string $url): void
    {
        if (
            strlen((string)$url) > 0
            && !filter_var((string)$url, FILTER_VALIDATE_URL)
        ) {
            $this->state->status()->setWarning(
                sprintf('Ungültige URL "%s"?', (string)$url)
            );
        }
    }

    /** @throws \Exception */
    private function flushAction(): void
    {
        if ($this->request->posted('action', '') === 'flush') {
            $this->files->deleteWorkDir();
            $this->files->deleteResultsWorkDir();
            $this->state->flush();
            $this->state->status()->setInfo(
                'Alle Daten wurden entfernt.'
            );
        }
    }

    /** @throws \Exception */
    private function checkAction(): void
    {
        if ($this->request->posted('action', '') === 'check') {
            try {
                (new DefaultChecker(
                    $this->rootDir,
                    $this->state,
                    $this->files,
                    $this->formats,
                    $this->logger
                ))->check();
            }
            catch (\Exception $e) {
                $this->logger->error(
                    'Profiling fehlgeschlagen: ' . $e->getMessage()
                );
                throw $e;
            }
        }
    }

    private function formAction(): void
    {
        // TODO sanitize incomings
        $this->setFormat();
        $this->setSource();
        $this->setAssets();
        $this->setIabMode();
        $this->setIframeMode();
    }

    private function setFormat(): void
    {
        $format = $this->request->posted('format', null);
        if ($format !== null) {
            $parameters = $this->state->data()->parameters();
            if ($format !== '') {
                $format = (string)$format;
                if (strlen($format) > 50) {
                    $this->badRequest('Format zu lang');
                }
                if (!$this->formats instanceof Formats) {
                    throw new Error();
                }
                if (!$this->formats->has($format)) {
                    $this->badRequest('Format nicht bekannt');
                }
                $parameters->setFormat($format);
            }
            else {
                $parameters->setFormat('');
            }
        }

        $parameters = $this->state->data()->parameters();

        $customInit = $this->request->posted('customInit', null);
        if ($customInit !== null) {
            $customInit = max(1, min(1000, (int)$customInit));
            $parameters->setCustomInit($customInit);
        }
        $customSub = $this->request->posted('customSub', null);
        if ($customSub !== null) {
            $customSub = max(1, min(1000, (int)$customSub));
            $parameters->setCustomSub($customSub);
        }
        $customWidth = $this->request->posted('customWidth', null);
        if ($customWidth !== null) {
            $customWidth = max(1, min(2000, (int)$customWidth));
            $parameters->setCustomWidth($customWidth);
        }
        $customHeight = $this->request->posted('customHeight', null);
        if ($customHeight !== null) {
            $customHeight = max(1, min(2000, (int)$customHeight));
            $parameters->setCustomHeight($customHeight);
        }
    }

    private function setSource(): void
    {
        $source = $this->request->posted('source', null);
        if ($source !== null) {
            $parameters = $this->state->data()->parameters();

            if (strlen((string)$source) > 65535) {
                $this->badRequest('Quelle zu lang');
            }
            $parameters->setSource((string)$source);
        }
    }

    private function setAssets(): void
    {
        $assets = $this->request->posted('assets', null);
        if ($assets !== null) {
            $parameters = $this->state->data()->parameters();

            $assets = array_values(
                array_filter(
                    array_map(
                        'trim',
                        (array)$assets
                    )
                )
            );
            if (count($assets) > 100) {
                $this->badRequest('Zuviele Assets');
            }
            foreach ($assets as $url) {
                if (strlen((string)$url) > 1024) {
                    $this->badRequest('URL zu lang');
                }
                $this->warnUrl((string)$url);
            }
            $parameters->setAssets($assets);
        }
    }

    private function badRequest(string $string): void
    {
        $this->logger->info(
            'Anfragefehler: ' . $string
        );
        $this->response->badRequest(
            $string
        );
    }

    private function setIabMode(): void
    {
        $value = $this->request->posted('iabMode', null);
        if ($value !== null) {
            $parameters = $this->state->data()->parameters();
            $parameters->setIabMode((int)$value);
        }
    }

    private function setIframeMode(): void
    {
        $value = $this->request->posted('iframeMode', null);
        if ($value !== null) {
            if (!in_array((string)$value, [
                'friendly',
                'unfriendly'
            ])) {
                $this->badRequest('Ungültiger Wert');
            }
            $parameters = $this->state->data()->parameters();
            $parameters->setIframeMode((string)$value);
        }
    }
}