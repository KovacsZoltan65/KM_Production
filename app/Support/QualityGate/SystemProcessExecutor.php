<?php

declare(strict_types=1);

namespace App\Support\QualityGate;

use RuntimeException;

final class SystemProcessExecutor implements ProcessExecutor
{
    public function execute(GateCommand $command, int $timeout): ProcessResult
    {
        $startedAt = microtime(true);
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $environment = $this->environment();
        $process = proc_open(
            $this->processCommand($command->arguments, $timeout),
            $descriptors,
            $pipes,
            getcwd() ?: null,
            $environment,
            ['bypass_shell' => true],
        );

        if (! is_resource($process)) {
            throw new RuntimeException("Unable to start command: {$command->label}");
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $status = proc_get_status($process);
        $processId = $status['pid'];
        $lastExitCode = -1;
        $timedOut = false;

        $watchdogTimeout = PHP_OS_FAMILY === 'Windows' ? $timeout + 90 : $timeout;

        while (true) {
            $status = proc_get_status($process);
            $this->forward($pipes[1], STDOUT);
            $this->forward($pipes[2], STDERR);

            if (! $status['running']) {
                $lastExitCode = (int) $status['exitcode'];
                break;
            }

            if (microtime(true) - $startedAt >= $watchdogTimeout) {
                $timedOut = true;
                $this->terminateProcessTree($process, $processId);
                break;
            }

            usleep(50_000);
        }

        $this->forward($pipes[1], STDOUT);
        $this->forward($pipes[2], STDERR);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $closedExitCode = proc_close($process);

        if ($lastExitCode < 0 && $closedExitCode >= 0) {
            $lastExitCode = $closedExitCode;
        }

        $timedOut = $timedOut || $lastExitCode === 124;

        return new ProcessResult(
            exitCode: $timedOut ? 124 : $lastExitCode,
            durationSeconds: microtime(true) - $startedAt,
            timedOut: $timedOut,
            processId: $processId,
        );
    }

    /**
     * @param  list<string>  $arguments
     * @return list<string>
     */
    private function processCommand(array $arguments, int $timeout): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return $arguments;
        }

        $json = json_encode($arguments, JSON_THROW_ON_ERROR);
        $encodedArguments = base64_encode($json);

        return [
            'pwsh.exe',
            '-NoProfile',
            '-NonInteractive',
            '-ExecutionPolicy',
            'Bypass',
            '-File',
            dirname(__DIR__, 3).'/tools/quality-gate-process.ps1',
            '-EncodedArguments',
            $encodedArguments,
            '-TimeoutSeconds',
            (string) $timeout,
        ];
    }

    /** @return array<string, string> */
    private function environment(): array
    {
        $environment = getenv();

        $environment['XDEBUG_MODE'] = 'off';
        $environment['NO_COLOR'] = '1';

        return $environment;
    }

    /** @param resource $source @param resource $destination */
    private function forward($source, $destination): void
    {
        $contents = stream_get_contents($source);

        if (is_string($contents) && $contents !== '') {
            fwrite($destination, $contents);
        }
    }

    /** @param resource $process */
    private function terminateProcessTree($process, ?int $processId): void
    {
        if ($processId !== null && $processId > 0 && PHP_OS_FAMILY === 'Windows') {
            proc_terminate($process, 9);
        } else {
            proc_terminate($process);

            if ($processId !== null && $processId > 0) {
                exec(sprintf('pkill -TERM -P %d >/dev/null 2>&1', $processId));
            }
        }

        usleep(200_000);
        $status = proc_get_status($process);

        if ($status['running']) {
            proc_terminate($process, 9);
        }
    }
}
