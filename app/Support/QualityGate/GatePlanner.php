<?php

declare(strict_types=1);

namespace App\Support\QualityGate;

use InvalidArgumentException;

final class GatePlanner
{
    public function __construct(private readonly ModuleMatrix $matrix) {}

    public function affected(SelectionResult $selection): GatePlan
    {
        return match ($selection->requiredLevel) {
            'full' => $this->full($selection),
            'integration' => $this->integration($selection),
            default => $this->fast($selection),
        };
    }

    public function fast(SelectionResult $selection): GatePlan
    {
        $commands = [];
        $phpFiles = $this->matchingFiles($selection->changedFiles, static fn (string $file): bool => str_ends_with($file, '.php'));
        $applicationPhpFiles = $this->matchingFiles($phpFiles, static fn (string $file): bool => str_starts_with($file, 'app/'));
        $documentationFiles = $this->matchingFiles($selection->changedFiles, static fn (string $file): bool => str_ends_with($file, '.md'));
        $modules = $selection->modules;
        $backend = $this->unique([...$selection->backendTests, ...$this->matrix->files($modules, 'backend')]);
        $frontend = $this->unique([...$selection->frontendTests, ...$this->matrix->files($modules, 'frontend')]);

        if ($phpFiles !== []) {
            $commands[] = $this->command('pint-changed', 'Pint (changed PHP files)', [PHP_BINARY, 'vendor/bin/pint', '--test', ...$phpFiles]);
        }

        if ($applicationPhpFiles !== []) {
            $commands[] = $this->command(
                'phpstan-changed',
                'PHPStan (changed application PHP files)',
                [PHP_BINARY, 'vendor/bin/phpstan', 'analyse', '--memory-limit=1G', '--no-progress', ...$applicationPhpFiles],
                'phpstan',
            );
        }

        if ($backend !== []) {
            $commands[] = $this->backendCommand('backend-affected', 'Affected backend tests', $backend);
        }

        if ($frontend !== []) {
            $commands[] = $this->command('frontend-affected', 'Affected frontend tests', ['npm', 'run', 'test:frontend', '--', ...$frontend], 'frontend');
        }

        if ($selection->requiresI18n) {
            $commands[] = $this->command('i18n', 'Translation synchronization', ['npm', 'run', 'i18n:check']);
        }

        if ($documentationFiles !== []) {
            $commands[] = $this->command('prettier-changed', 'Prettier (changed documentation)', ['npx', 'prettier', '--check', ...$documentationFiles]);
        }

        if ($selection->requiresPlaywright) {
            $playwright = $this->unique([...$selection->playwrightTests, ...$this->matrix->files($modules, 'playwright')]);
            $commands = [...$commands, ...$this->playwrightCommands($playwright, $selection->requiresBuild)];
        }

        $commands[] = $this->command('diff-check', 'Patch whitespace', ['git', 'diff', '--check']);

        return $this->plan('fast', $modules, $selection, $commands);
    }

    public function module(string $module): GatePlan
    {
        if (! $this->matrix->has($module)) {
            throw new InvalidArgumentException("Unknown quality gate module: {$module}");
        }

        $modules = $this->matrix->expandRelated([$module]);
        $selection = new SelectionResult([]);
        $selection->modules = $modules;
        $backend = $this->matrix->files($modules, 'backend');
        $frontend = $this->matrix->files($modules, 'frontend');
        $playwright = $this->matrix->files($modules, 'playwright');
        $commands = [
            $this->command('pint-full', 'Pint', [PHP_BINARY, 'vendor/bin/pint', '--test']),
            $this->command('phpstan-full', 'PHPStan', ['composer', 'analyse'], 'phpstan'),
        ];

        if ($backend !== []) {
            $commands[] = $this->backendCommand('backend-module', 'Module backend regression', $backend);
        }

        if ($frontend !== []) {
            $commands[] = $this->command('frontend-module', 'Module frontend regression', ['npm', 'run', 'test:frontend', '--', ...$frontend], 'frontend');
        }

        $commands[] = $this->command('i18n', 'Translation synchronization', ['npm', 'run', 'i18n:check']);

        if ($this->matrix->moduleNeedsBuild($modules)) {
            $commands[] = $this->command('build', 'Production build', ['npm', 'run', 'build'], 'build');
        }

        $commands = [...$commands, ...$this->playwrightCommands($playwright, false)];
        $commands[] = $this->command('diff-check', 'Patch whitespace', ['git', 'diff', '--check']);

        return $this->plan('module', $modules, $selection, $commands);
    }

    public function integration(?SelectionResult $selection = null): GatePlan
    {
        $selection ??= new SelectionResult([]);
        $requestedModules = $selection->modules === [] ? $this->matrix->availableModules() : $selection->modules;
        $modules = $this->matrix->expandRelated($requestedModules);
        $backend = $this->unique([
            ...$this->matrix->files($modules, 'backend'),
            ...$this->matrix->integrationFiles('backend'),
        ]);
        $playwright = $this->matrix->integrationFiles('playwright');
        $commands = [
            $this->command('pint-full', 'Pint', [PHP_BINARY, 'vendor/bin/pint', '--test']),
            $this->command('phpstan-full', 'PHPStan', ['composer', 'analyse'], 'phpstan'),
            $this->backendCommand('backend-integration', 'Integration backend regression', $backend),
            $this->command('frontend-full', 'Frontend regression', ['npm', 'run', 'test:frontend'], 'frontend'),
            $this->command('i18n', 'Translation synchronization', ['npm', 'run', 'i18n:check']),
            $this->command('build', 'Production build', ['npm', 'run', 'build'], 'build'),
            ...$this->playwrightCommands($playwright, false),
            $this->command('diff-check', 'Patch whitespace', ['git', 'diff', '--check']),
        ];

        return $this->plan('integration', $modules, $selection, $commands);
    }

    public function full(?SelectionResult $selection = null): GatePlan
    {
        $selection ??= new SelectionResult([]);
        $commands = [
            $this->command('composer-validate', 'Composer validation', ['composer', 'validate', '--strict']),
            $this->command('composer-audit', 'Composer security audit', ['composer', 'audit']),
            $this->command('pint-full', 'Pint', [PHP_BINARY, 'vendor/bin/pint', '--test']),
            $this->command('phpstan-full', 'PHPStan', ['composer', 'analyse'], 'phpstan'),
            $this->command('backend-full', 'Complete SQLite backend suite', ['composer', 'test:backend:sqlite'], 'backend'),
            $this->command('migrations-sqlite', 'SQLite migration round-trip', ['composer', 'test:backend:migrations:sqlite'], 'backend'),
            $this->command('frontend-coverage', 'Frontend suite with coverage', ['npm', 'run', 'test:frontend:coverage'], 'frontend'),
            $this->command('i18n', 'Translation synchronization', ['npm', 'run', 'i18n:check']),
            $this->command('prettier-full', 'Prettier', ['npm', 'run', 'format:check']),
            $this->command('npm-audit', 'npm dependency audit', ['npm', 'audit'], 'frontend'),
            $this->command('npm-audit-production', 'npm production dependency audit', ['npm', 'audit', '--omit=dev'], 'frontend'),
            $this->command('build', 'Production build', ['npm', 'run', 'build'], 'build'),
            ...$this->playwrightCommands(['tests/e2e/admin'], false),
            $this->command('diff-check', 'Patch whitespace', ['git', 'diff', '--check']),
        ];

        return $this->plan('full', $this->matrix->availableModules(), $selection, $commands);
    }

    /**
     * @param  list<string>  $files
     */
    private function backendCommand(string $id, string $label, array $files): GateCommand
    {
        return $this->command(
            $id,
            $label,
            [PHP_BINARY, 'scripts/backend-test-environment.php', 'sqlite', 'test', ...$this->unique($files)],
            'backend',
        );
    }

    /**
     * @param  list<string>  $paths
     * @return list<GateCommand>
     */
    private function playwrightCommands(array $paths, bool $includeBuild): array
    {
        if ($paths === []) {
            return [];
        }

        $commands = [
            $this->command('e2e-prepare', 'Prepare isolated E2E environment', ['npm', 'run', 'test:e2e:prepare'], 'playwright'),
        ];

        if ($includeBuild) {
            $commands[] = $this->command('build', 'Production build', ['npm', 'run', 'build'], 'build');
        }

        $commands[] = $this->command(
            'playwright',
            'Playwright Chromium regression',
            ['npx', 'playwright', 'test', ...$this->unique($paths), '--project=chromium'],
            'playwright',
        );

        return $commands;
    }

    /**
     * @param  list<GateCommand>  $commands
     */
    private function plan(string $level, array $modules, SelectionResult $selection, array $commands): GatePlan
    {
        $deduplicated = [];

        foreach ($commands as $command) {
            $deduplicated[$command->id] = $command;
        }

        return new GatePlan(
            level: $level,
            modules: $this->unique($modules),
            changedFiles: $selection->changedFiles,
            commands: array_values($deduplicated),
            explanations: $selection->explanations,
        );
    }

    /** @param list<string> $arguments */
    private function command(string $id, string $label, array $arguments, string $timeoutGroup = 'default'): GateCommand
    {
        return new GateCommand($id, $label, $arguments, $timeoutGroup);
    }

    /**
     * @param  list<string>  $files
     * @return list<string>
     */
    private function matchingFiles(array $files, callable $predicate): array
    {
        return array_values(array_filter($files, $predicate));
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private function unique(array $values): array
    {
        return array_values(array_unique($values));
    }
}
