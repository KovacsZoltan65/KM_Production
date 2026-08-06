<?php

declare(strict_types=1);

namespace App\Support\QualityGate;

use InvalidArgumentException;

final class ModuleMatrix
{
    /**
     * @param  array<string, mixed>  $configuration
     */
    public function __construct(private readonly array $configuration) {}

    /** @return list<string> */
    public function availableModules(): array
    {
        $modules = array_keys($this->configuration['modules'] ?? []);
        sort($modules);

        return $modules;
    }

    public function has(string $module): bool
    {
        return isset($this->configuration['modules'][$module]);
    }

    /**
     * @return list<string>
     */
    public function expandRelated(array $modules): array
    {
        $resolved = [];
        $visiting = array_values(array_unique($modules));

        while ($visiting !== []) {
            $module = array_shift($visiting);

            if (! is_string($module) || isset($resolved[$module])) {
                continue;
            }

            if (! $this->has($module)) {
                throw new InvalidArgumentException("Unknown quality gate module: {$module}");
            }

            $resolved[$module] = true;

            foreach ($this->module($module)['related_modules'] ?? [] as $related) {
                if (is_string($related) && ! isset($resolved[$related])) {
                    $visiting[] = $related;
                }
            }
        }

        $result = array_keys($resolved);
        sort($result);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function module(string $module): array
    {
        if (! $this->has($module)) {
            throw new InvalidArgumentException("Unknown quality gate module: {$module}");
        }

        return $this->configuration['modules'][$module];
    }

    /**
     * @param  list<string>  $modules
     * @return list<string>
     */
    public function files(array $modules, string $kind): array
    {
        $files = [];

        foreach ($modules as $module) {
            foreach ($this->module($module)[$kind] ?? [] as $path) {
                if (is_string($path)) {
                    $files[$path] = true;
                }
            }
        }

        return array_keys($files);
    }

    /**
     * @return list<string>
     */
    public function integrationFiles(string $kind): array
    {
        $paths = $this->configuration['integration'][$kind] ?? [];

        return array_values(array_filter($paths, 'is_string'));
    }

    public function moduleNeedsBuild(array $modules): bool
    {
        foreach ($modules as $module) {
            if (($this->module($module)['build'] ?? false) === true) {
                return true;
            }
        }

        return false;
    }
}
