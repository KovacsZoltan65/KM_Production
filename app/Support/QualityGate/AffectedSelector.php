<?php

declare(strict_types=1);

namespace App\Support\QualityGate;

final class AffectedSelector
{
    private const LEVEL_PRIORITY = ['fast' => 0, 'module' => 1, 'integration' => 2, 'full' => 3];

    /**
     * @param  array<string, mixed>  $configuration
     */
    public function __construct(private readonly array $configuration) {}

    /**
     * @param  list<string>  $changedFiles
     */
    public function select(array $changedFiles): SelectionResult
    {
        $files = array_values(array_unique(array_map(PathMatcher::normalize(...), $changedFiles)));
        sort($files);
        $selection = new SelectionResult($files);

        foreach ($files as $file) {
            $matched = false;

            if ($this->matchesAny($file, $this->configuration['full_risk_patterns'] ?? [])) {
                $this->raiseLevel($selection, 'full');
                $this->explain($selection, $file, 'high-risk configuration or database rule -> full gate');
                $matched = true;
            } elseif ($this->matchesAny($file, $this->configuration['integration_risk_patterns'] ?? [])) {
                $this->raiseLevel($selection, 'integration');
                $this->explain($selection, $file, 'shared/core rule -> integration gate');
                $matched = true;
            }

            foreach ($this->configuration['rules'] ?? [] as $rule) {
                if (! is_array($rule) || ! $this->matchesAny($file, $rule['patterns'] ?? [])) {
                    continue;
                }

                $matched = true;
                $name = is_string($rule['name'] ?? null) ? $rule['name'] : 'module rule';

                foreach ($rule['modules'] ?? [] as $module) {
                    if (is_string($module)) {
                        $selection->modules[] = $module;
                        $this->explain($selection, $file, "matched rule: {$name} -> module: {$module}");
                    }
                }

                if (($rule['playwright'] ?? false) === true && $this->isWorkflowRelevant($file)) {
                    $selection->requiresPlaywright = true;
                    $this->explain($selection, $file, 'workflow/UI change -> Playwright required');
                }
            }

            if (str_starts_with($file, 'tests/Feature/') || str_starts_with($file, 'tests/Unit/')) {
                $selection->backendTests[] = $file;
                $this->explain($selection, $file, 'changed backend test -> run directly');
                $matched = true;
            }

            if (str_starts_with($file, 'tests/frontend/') && str_ends_with($file, '.test.js')) {
                $selection->frontendTests[] = $file;
                $this->explain($selection, $file, 'changed frontend test -> run directly');
                $matched = true;
            }

            if (str_starts_with($file, 'tests/e2e/') && str_ends_with($file, '.spec.js')) {
                $selection->playwrightTests[] = $file;
                $selection->requiresPlaywright = true;
                $this->explain($selection, $file, 'changed Playwright spec -> run directly');
                $matched = true;
            }

            if (str_starts_with($file, 'lang/')
                || str_starts_with($file, 'resources/lang/')
                || $file === 'scripts/check-translations.js') {
                $selection->requiresI18n = true;
                $this->explain($selection, $file, 'translation rule -> i18n check');
                $matched = true;
            }

            if (str_starts_with($file, 'resources/js/')) {
                $selection->requiresI18n = true;
                $selection->requiresBuild = true;
            }

            if ($this->isDocumentationOnly($file)) {
                $this->explain($selection, $file, 'documentation-only change -> formatting/whitespace checks');
                $matched = true;
            }

            if (! $matched) {
                $this->raiseLevel($selection, 'integration');
                $this->explain($selection, $file, 'no safe module rule -> integration fallback');
            }
        }

        $selection->modules = $this->uniqueSorted($selection->modules);
        $selection->backendTests = $this->uniqueSorted($selection->backendTests);
        $selection->frontendTests = $this->uniqueSorted($selection->frontendTests);
        $selection->playwrightTests = $this->uniqueSorted($selection->playwrightTests);

        return $selection;
    }

    /**
     * @param  list<string>  $patterns
     */
    private function matchesAny(string $file, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (PathMatcher::matches($file, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function isWorkflowRelevant(string $file): bool
    {
        if (str_starts_with($file, 'tests/e2e/') || str_starts_with($file, 'resources/js/Pages/')) {
            return true;
        }

        return str_contains($file, '/Controllers/')
            || str_contains($file, '/Requests/')
            || str_contains($file, '/Services/')
            || str_starts_with($file, 'routes/');
    }

    private function isDocumentationOnly(string $file): bool
    {
        return str_starts_with($file, 'docs/')
            || str_starts_with($file, '.kiro/')
            || str_ends_with($file, '.md');
    }

    private function raiseLevel(SelectionResult $selection, string $level): void
    {
        if (self::LEVEL_PRIORITY[$level] > self::LEVEL_PRIORITY[$selection->requiredLevel]) {
            $selection->requiredLevel = $level;
        }
    }

    private function explain(SelectionResult $selection, string $file, string $reason): void
    {
        $selection->explanations[$file] ??= [];
        $selection->explanations[$file][] = $reason;
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private function uniqueSorted(array $values): array
    {
        $values = array_values(array_unique($values));
        sort($values);

        return $values;
    }
}
