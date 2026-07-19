<?php

namespace App\Console\Commands;

use App\Support\Testing\TestEnvironmentGuard;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;

class AssertSafeTestingEnvironment extends Command
{
    protected $signature = 'testing:environment-check';

    protected $description = 'Ellenőrzi, hogy a backend tesztkörnyezet biztonságosan izolált-e';

    public function handle(Repository $config): int
    {
        TestEnvironmentGuard::assertLaravelConfiguration($config);

        $this->components->info('A backend tesztkörnyezet biztonságosan izolált.');

        return self::SUCCESS;
    }
}
