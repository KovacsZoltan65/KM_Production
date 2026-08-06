<?php

declare(strict_types=1);

namespace App\Support\QualityGate;

final class SelectionResult
{
    /** @var list<string> */
    public array $modules = [];

    /** @var list<string> */
    public array $backendTests = [];

    /** @var list<string> */
    public array $frontendTests = [];

    /** @var list<string> */
    public array $playwrightTests = [];

    /** @var array<string, list<string>> */
    public array $explanations = [];

    public string $requiredLevel = 'fast';

    public bool $requiresPlaywright = false;

    public bool $requiresI18n = false;

    public bool $requiresBuild = false;

    /**
     * @param  list<string>  $changedFiles
     */
    public function __construct(public readonly array $changedFiles) {}
}
