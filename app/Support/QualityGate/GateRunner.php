<?php

declare(strict_types=1);

namespace App\Support\QualityGate;

final class GateRunner
{
    /**
     * @param  array<string, int>  $timeouts
     */
    public function __construct(
        private readonly ProcessExecutor $executor,
        private readonly array $timeouts,
        private readonly ?int $timeoutOverride = null,
    ) {}

    public function run(GatePlan $plan, bool $dryRun = false): int
    {
        $this->printPlan($plan, $dryRun);

        if ($dryRun) {
            return 0;
        }

        $gateStartedAt = microtime(true);

        foreach ($plan->commands as $index => $command) {
            $timeout = $this->timeoutOverride
                ?? $command->timeout
                ?? $this->timeouts[$command->timeoutGroup]
                ?? $this->timeouts['default']
                ?? 120;
            $number = $index + 1;
            $total = count($plan->commands);
            fwrite(STDOUT, PHP_EOL."[{$number}/{$total}] {$command->label} (timeout: {$timeout}s)".PHP_EOL);
            $result = $this->executor->execute($command, $timeout);

            if (! $result->successful()) {
                $reason = $result->timedOut ? 'TIMEOUT' : "EXIT {$result->exitCode}";
                fwrite(STDERR, sprintf(
                    'Quality gate failed [%s] after %.2fs: %s%s',
                    $reason,
                    $result->durationSeconds,
                    $command->display(),
                    PHP_EOL,
                ));

                return $result->exitCode === 0 ? 1 : $result->exitCode;
            }

            fwrite(STDOUT, sprintf('PASS %.2fs%s', $result->durationSeconds, PHP_EOL));
        }

        fwrite(STDOUT, sprintf(
            '%sQuality gate passed: %s (%d commands, %.2fs)%s',
            PHP_EOL,
            $plan->level,
            count($plan->commands),
            microtime(true) - $gateStartedAt,
            PHP_EOL,
        ));

        return 0;
    }

    private function printPlan(GatePlan $plan, bool $dryRun): void
    {
        fwrite(STDOUT, 'Quality gate: '.$plan->level.($dryRun ? ' [dry-run]' : '').PHP_EOL);
        fwrite(STDOUT, 'Detected modules: '.($plan->modules === [] ? '(none)' : implode(', ', $plan->modules)).PHP_EOL);

        if ($plan->changedFiles !== []) {
            fwrite(STDOUT, 'Changed files:'.PHP_EOL);
            foreach ($plan->changedFiles as $file) {
                fwrite(STDOUT, "- {$file}".PHP_EOL);
            }
        }

        fwrite(STDOUT, 'Commands:'.PHP_EOL);
        foreach ($plan->commands as $command) {
            fwrite(STDOUT, "- [{$command->timeoutGroup}] {$command->display()}".PHP_EOL);
        }
    }
}
