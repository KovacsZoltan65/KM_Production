<?php

declare(strict_types=1);

use App\Support\QualityGate\AffectedSelector;
use App\Support\QualityGate\ChangedFilesDetector;
use App\Support\QualityGate\GatePlanner;
use App\Support\QualityGate\GateRunner;
use App\Support\QualityGate\ModuleMatrix;
use App\Support\QualityGate\SystemProcessExecutor;

require dirname(__DIR__).'/vendor/autoload.php';

$configuration = require dirname(__DIR__).'/config/quality-gates.php';
$arguments = array_slice($argv, 1);
$mode = array_shift($arguments) ?? 'affected';
$dryRun = false;
$explain = false;
$base = null;
$timeout = null;
$positional = [];

foreach ($arguments as $argument) {
    if ($argument === '--dry-run') {
        $dryRun = true;
    } elseif ($argument === '--explain') {
        $explain = true;
    } elseif (str_starts_with($argument, '--base=')) {
        $base = substr($argument, 7);
    } elseif (str_starts_with($argument, '--timeout=')) {
        $value = substr($argument, 10);

        if (filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            fwrite(STDERR, "--timeout must be a positive integer in seconds.\n");
            exit(64);
        }

        $timeout = (int) $value;
    } elseif (str_starts_with($argument, '--')) {
        fwrite(STDERR, "Unknown option: {$argument}\n");
        exit(64);
    } else {
        $positional[] = $argument;
    }
}

$matrix = new ModuleMatrix($configuration);
$planner = new GatePlanner($matrix);
$selection = null;

try {
    $plan = match ($mode) {
        'affected', 'fast' => (static function () use ($configuration, $planner, $base, &$selection) {
            $changedFiles = (new ChangedFilesDetector)->detect($base);
            $selection = (new AffectedSelector($configuration))->select($changedFiles);

            return $planner->affected($selection);
        })(),
        'module' => (static function () use ($planner, $matrix, $positional) {
            $module = $positional[0] ?? null;

            if (! is_string($module) || ! $matrix->has($module)) {
                $available = implode(', ', $matrix->availableModules());
                throw new InvalidArgumentException('Unknown or missing module. Available modules: '.$available);
            }

            return $planner->module($module);
        })(),
        'integration' => $planner->integration(),
        'full' => $planner->full(),
        'modules' => (static function () use ($matrix) {
            fwrite(STDOUT, implode(PHP_EOL, $matrix->availableModules()).PHP_EOL);
            exit(0);
        })(),
        default => throw new InvalidArgumentException("Unknown quality gate level: {$mode}"),
    };
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage().PHP_EOL);
    exit(64);
}

if ($explain) {
    fwrite(STDOUT, 'Selection explanation:'.PHP_EOL);

    if ($plan->explanations === []) {
        fwrite(STDOUT, '- no changed-file rules were evaluated for this explicit gate'.PHP_EOL);
    }

    foreach ($plan->explanations as $file => $reasons) {
        fwrite(STDOUT, $file.PHP_EOL);
        foreach ($reasons as $reason) {
            fwrite(STDOUT, "  -> {$reason}".PHP_EOL);
        }
    }
}

$timeouts = $configuration['timeouts'] ?? [];

foreach (array_keys($timeouts) as $group) {
    $environmentName = 'QUALITY_GATE_TIMEOUT_'.strtoupper($group);
    $environmentValue = getenv($environmentName);

    if (is_string($environmentValue) && ctype_digit($environmentValue) && (int) $environmentValue > 0) {
        $timeouts[$group] = (int) $environmentValue;
    }
}

$runner = new GateRunner(new SystemProcessExecutor, $timeouts, $timeout);
exit($runner->run($plan, $dryRun));
