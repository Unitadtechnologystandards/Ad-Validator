<?php
declare(strict_types=1);

namespace OVKAC\Checker;

use OVKAC\Api\Files;
use OVKAC\Logger\Logger;
use OVKAC\State\State;

final class DefaultAdTemplate implements AdTemplate
{
    private $logger;
    private $files;
    private $state;
    private $host;

    public function __construct(
        string $host,
        State $state,
        Files $files,
        Logger $logger
    )
    {
        $this->host = $host;
        $this->logger = $logger;
        $this->files = $files;
        $this->state = $state;
    }

    public function create(): void
    {
        $this->logger->debug('Create ovkadcheck.html');

        $workDir = $this->state->data()->parameters()->workDir();

        $this->files->create(
            $workDir,
            'ovkadcheck.html',
            $this->template()
        );
    }

    public function preview(): string
    {
        $this->logger->debug('Create preview');

        $workDir = $this->state->data()->parameters()->workDir();

        $params = $this->state->data()->parameters();

        $archiveName = $params->archiveName();
        if (strlen($archiveName) > 0) {
            try {
                return sprintf(
                    '%s/workdir/%s',
                    $this->host,
                    $this->files->indexFile()
                );
            }
            catch (\Exception $e) {
                throw $e;
            }
        }
        else {

            $source = $params->source();
            ob_start();
            require_once __DIR__ . '/ovkpreview.html.php';
            $html = ob_get_clean();
            if ($html === false) {
                throw new \Exception(
                    'Creating ovkadcheck.html failed'
                );
            }

            $this->files->create(
                $workDir,
                'ovkpreview.html',
                $html
            );

            return sprintf(
                '%s/workdir/%s/%s',
                $this->host,
                $workDir,
                'ovkpreview.html'
            );
        }
    }

    /** @throws \Exception */
    private function template(): string
    {
        $params = $this->state->data()->parameters();

        $iframe = '';
        $assets = '';
        $source = $params->source();
        $iabMode = $params->iabMode();

        foreach ($params->assets() as $assetUrl) {
            $ext = pathinfo(
                (string)parse_url(
                    (string)$assetUrl,
                    PHP_URL_PATH
                ),
                PATHINFO_EXTENSION
            );
            switch ($ext) {
                case 'js':
                    $assets .= '<script src="' . $assetUrl . '"></script>';
                    break;
                case 'html':
                    $assets .= '<iframe src="' . $assetUrl . '"></iframe>';
                    break;
                case 'css':
                    $assets .= '<link rel="stylesheet" href="' . $assetUrl . '"></link>';
                    break;
                default:
                    $assets .= '<img src="' . $assetUrl . '" />';
                    break;
            }
        }

        $host = $this->host; // friendly

        if ($params->iframeMode() === 'unfriendly') {
            $host = sprintf(
                '%s://unfriendly-%s',
                parse_url($host, PHP_URL_SCHEME),
                parse_url($host, PHP_URL_HOST)
            );
        }

        $archiveName = $params->archiveName();
        if (strlen($archiveName) > 0) {
            $iframe = sprintf(
                '%s/workdir/%s',
                $host,
                $this->files->indexFile()
            );
        }
        elseif (strlen(trim($source))) {
            $workDir = $this->state->data()->parameters()->workDir();
            $this->files->create(
                $workDir,
                'ovkindex.html',
                $source
            );
            $iframe = sprintf(
                '%s/workdir/%s/ovkindex.html',
                $host,
                $workDir
            );
        }

        ob_start();
        require_once __DIR__ . '/ovkadcheck.html.php';
        $html = ob_get_clean();
        if ($html === false) {
            throw new \Exception(
                'Creating ovkadcheck.html failed'
            );
        }

        return (string)$html;
    }

}