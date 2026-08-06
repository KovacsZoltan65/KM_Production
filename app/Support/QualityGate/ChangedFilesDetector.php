<?php

declare(strict_types=1);

namespace App\Support\QualityGate;

use RuntimeException;

final class ChangedFilesDetector
{
    /**
     * @return list<string>
     */
    public function detect(?string $base = null): array
    {
        $commands = $base === null
            ? [
                ['git', 'diff', '--name-only'],
                ['git', 'diff', '--name-only', '--cached'],
                ['git', 'diff', '--name-only', 'HEAD'],
            ]
            : [
                ['git', 'diff', '--name-only', $base.'...HEAD'],
                ['git', 'diff', '--name-only'],
                ['git', 'diff', '--name-only', '--cached'],
            ];

        $commands[] = ['git', 'ls-files', '--others', '--exclude-standard'];
        $files = [];

        foreach ($commands as $command) {
            foreach ($this->run($command) as $file) {
                $files[PathMatcher::normalize($file)] = true;
            }
        }

        $result = array_keys($files);
        sort($result);

        return $result;
    }

    /**
     * @param  list<string>  $arguments
     * @return list<string>
     */
    private function run(array $arguments): array
    {
        $command = implode(' ', array_map(escapeshellarg(...), $arguments));
        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException('Git change detection failed: '.$command);
        }

        return array_values(array_filter(array_map('trim', $output), static fn (string $line): bool => $line !== ''));
    }
}
