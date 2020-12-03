<?php
declare(strict_types=1);

namespace OVKAC\Checker;

use OVKAC\Api\Files;
use OVKAC\Formats\DefaultFormat;
use OVKAC\Formats\Formats;
use OVKAC\Logger\Logger;
use OVKAC\State\State;
use OVKAC\Vendors\DefaultTcfVendors;
use OVKAC\Vendors\DefaultVendors;
use OVKAC\Vendors\TcfVendors;
use OVKAC\Vendors\Vendors;

final class DefaultChecker implements Checker
{
    private $logger;
    private $files;
    private $state;
    private $formats;

    private $timeout = 5000;
    private $maxWait = 10000;

    private $rootDir;

    /** @var Vendors */
    private $vendors;
    private $vendorUrl = 'https://cdn.stroeerdigitalgroup.de/metatag/ovk/vendorinfo.json';

    /** @var TcfVendors */
    private $tcfVendors;
    private $tcfConfigUrl = ''; // TODO set location of remote tcf vendors config json (https://somewhere/tcfconfig.json)

    private $nodeBinary = '/usr/bin/node';

    public function __construct(
        string $rootDir,
        State $state,
        Files $files,
        Formats $formats,
        Logger $logger
    )
    {
        $this->rootDir = $rootDir;
        $this->state = $state;
        $this->files = $files;
        $this->logger = $logger;
        $this->formats = $formats;
    }

    public function check(): void
    {
        $this->prepare();

        $url = sprintf(
            '%s/workdir/%s/ovkadcheck.html',
            $this->host(),
            $this->state->data()->parameters()->workDir()
        );

        $command = sprintf(
            'timeout 60 %s src/Js/profiler.js %s %s %s',
            $this->nodeBinary,
            $url,
            (string)$this->timeout,
            (string)$this->maxWait
        );
        $this->logger->info(
            sprintf(
                'Profiling: %s',
                $command
            )
        );

        // -------------------------

        $descr = [
            ['pipe', 'r'],
            ['pipe', 'w'],
            ['pipe', 'w'],
        ];
        $pipes = [];
        $process = proc_open(
            $command,
            $descr,
            $pipes,
            sprintf(
                '%s',
                $this->rootDir

            ),
            null
        );

        $results = (string)stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        // -------------------------

        if (strlen((string)$stderr) > 0) {
            $this->logger->error(
                sprintf(
                    'Errors when checking %s stderr: %s',
                    $url,
                    trim((string)$stderr)
                )
            );
        }

        $status = 1;
        if (is_resource($process)) {
            $status = proc_close($process);
        }
        if ($status === 1) {
            $this->logger->error('Process returned with 1');
            throw new \Exception('PROCFAILED');
        }
        elseif ($status === 124) {
            $this->logger->error('Process returned with 124 (timeout)');
            $this->state->status()->setError('Timeout!');
            return;
        }

        $profiling = @json_decode(
            $results,
            true
        );
        if (
            $profiling !== false
            && is_array($profiling)
        ) {
            $woItems = $profiling;
            unset($woItems['items']);
            $this->logger->debug(
                json_encode(
                    $woItems,
                    JSON_PRETTY_PRINT
                )
            );

            $this->state->data()->results()->setProfiling(
                $this->extendProfiling(
                    $profiling
                )
            );
        }
        else {
            if (
                json_last_error() > 0
                && strlen(json_last_error_msg()) > 0
            ) {
                $this->logger->error(
                    'Json decode failed: ' . json_last_error_msg()
                );
                $this->logger->debug(
                    'Results: ' . $results
                );
            }
            else {
                $this->logger->error(
                    'Profiling failed'
                );
            }
        }
    }

    /** @throws \Exception */
    private function tcfVendors(): void
    {
        $this->tcfVendors = (new DefaultTcfVendors(
            $this->logger,
            $this->state->data()->parameters()->tcfVendors(),
            sprintf(
                '%s/var/cache',
                $this->rootDir
            ),
            'tcfconfig.json',
            $this->tcfConfigUrl
        ))->load();
    }

    /** @throws \Exception */
    private function vendors(): void
    {
        $this->vendors = (new DefaultVendors(
            $this->logger,
            'default',
            sprintf(
                '%s/var/cache/ovk_vendorinfo.json',
                $this->rootDir
            ),
            $this->vendorUrl,
            86400,
            true
        ))->load();
    }

    /** @throws \Exception */
    private function prepare(): void
    {
        $parameters = $this->state->data()->parameters();

        $this->files->ensureWorkDir();
        $this->files->deleteResultsWorkDir();

        $results = $this->state->data()->results();
        $results->create();
        $results->setProfiling([]);

        $results->setFormat(
            $parameters->format() === '0'
                ? new DefaultFormat(
                '0',
                'Benutzer',
                $parameters->customInit(),
                $parameters->customSub(),
                $parameters->customWidth(),
                $parameters->customHeight()
            )
                : $this->formats->get(
                $parameters->format()
            )
        );

        $results->setArchiveName(
            $parameters->archiveName()
        );

        $this->vendors();
        $this->tcfVendors();

        $this->subject();

        $results->setPreviewUrl(
            $this->preview()
        );

        $results->setWorkDir(
            $parameters->workDir()
        );
    }

    /** @throws \Exception */
    private function subject(): void
    {
        (new DefaultAdTemplate(
            $this->host(),
            $this->state,
            $this->files,
            $this->logger
        ))->create();
    }

    /** @throws \Exception */
    private function preview(): string
    {
        return (new DefaultAdTemplate(
            $this->host(),
            $this->state,
            $this->files,
            $this->logger
        ))->preview();
    }

    private function extendProfiling(
        array $profiling
    ): array
    {
        array_shift($profiling['items']);

        $tcfVendors = [
            'lists' => $this->tcfVendors->names(),
            'vendors' => []
        ];

        $gdprMacro = false;

        foreach ($profiling['items'] as $k => $item) {
            $v = $this->vendors->find(
                $item['url']
            );
            $profiling['items'][$k]['vendor'] = $v;
            if ($v !== null) {
                $id = (int)$v['id'];
                $name = (string)$v['name'];
                if (!isset($tcfVendors['vendors'][$id])) {
                    $tcfVendors['vendors'][$id] = [
                        'name' => $name,
                        'matches' => $this->tcfVendors->find(
                            $id
                        )
                    ];
                }
            }

            if ($this->gdprMacro($item['url'])) {
                $gdprMacro = true;
            }
        }

        $profiling['tcfVendors'] = $tcfVendors;

        $profiling['gdprMacro'] = $gdprMacro;

        return $profiling;
    }

    private function host(): string
    {
        return sprintf(
            '%s://%s',
            $_SERVER['REQUEST_SCHEME'],
            $_SERVER['HTTP_HOST']
        );
    }

    private function gdprMacro(string $url): bool
    {
        return (strpos($url, '${gdpr}') !== false && strpos($url, '${gdpr_consent}') !== false);
    }

}