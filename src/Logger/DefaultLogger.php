<?php
declare(strict_types=1);

namespace OVKAC\Logger;

final class DefaultLogger implements Logger
{
    public function debug(string $message, array $context = []): void
    {
        $this->_log(LogLevel::DEBUG, $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->_log(LogLevel::INFO, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->_log(LogLevel::ERROR, $message, $context);
    }

    public function log(
        string $level,
        string $message,
        array $context = []
    ): void
    {
        $this->_log($level, $message, $context);
    }

    private function _log(
        string $level,
        string $message,
        array $context = []
    ): void
    {
        echo sprintf(
            "[%s] %s",
            $level,
            $this->termOutput(
                $message,
                $this->colorForLevel($level)
            )
        );
    }

    private function colorForLevel(string $level): string
    {
        switch ($level) {
            case LogLevel::INFO:
                return 'YELLOW';
            case LogLevel::ERROR:
                return 'RED';
        }
        return 'GREEN';
    }

    private function termOutput(
        string $string,
        string $colorCode = 'NORMAL'
    ): string
    {
        $colors = array(
            'LIGHT_RED' => '[1;31m',
            'LIGHT_GREEN' => '[1;32m',
            'YELLOW' => '[1;33m',
            'LIGHT_BLUE' => '[1;34m',
            'MAGENTA' => '[1;35m',
            'LIGHT_CYAN' => '[1;36m',
            'WHITE' => '[1;37m',
            'NORMAL' => '[0m',
            'BLACK' => '[0;30m',
            'RED' => '[0;31m',
            'GREEN' => '[0;32m',
            'BROWN' => '[0;33m',
            'BLUE' => '[0;34m',
            'CYAN' => '[0;36m',
            'BOLD' => '[1m',
            'UNDERSCORE' => '[4m',
            'REVERSE' => '[7m',
            'ERROR' => '[1;41m',
        );
        $color = isset($colors[$colorCode])
            ? chr(27) . $colors[$colorCode]
            : chr(27) . $colors['NORMAL'];
        if (!$this->ttyHasColors()) {
            return $string;
        }
        return $color . $string . chr(27) . $colors['NORMAL'];
    }

    private function ttyHasColors(): bool
    {
        if (DIRECTORY_SEPARATOR == '\\') { // is windows
            $supported = (false !== getenv('ANSICON'));
        }
        else {
            $supported =
                function_exists('posix_isatty')
                && @posix_isatty(STDOUT);
        }
        return $supported;
    }


}