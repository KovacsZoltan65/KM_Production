<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Testing\TestEnvironmentGuard;
use Database\Seeders\InitialInstallationSeeder;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseRoundTrip extends Command
{
    protected $signature = 'testing:database-round-trip';

    protected $description = 'Biztonságos fresh, rollback, migrate és alapseeder smoke teszt';

    public function handle(Repository $config): int
    {
        TestEnvironmentGuard::assertLaravelConfiguration($config);

        if (! $this->executeArtisanCommand('migrate:fresh', ['--force' => true])) {
            return self::FAILURE;
        }

        if (! $this->executeArtisanCommand('migrate:rollback', ['--force' => true])) {
            return self::FAILURE;
        }

        if (! $this->executeArtisanCommand('migrate', ['--force' => true])) {
            return self::FAILURE;
        }

        foreach ([1, 2] as $attempt) {
            if (! $this->executeArtisanCommand('db:seed', [
                '--class' => InitialInstallationSeeder::class,
                '--force' => true,
            ])) {
                return self::FAILURE;
            }

            $this->components->info("Alapseeder smoke futás kész: {$attempt}/2.");
        }

        if (Role::query()->where('name', 'super-admin')->doesntExist()
            || Permission::query()->doesntExist()
            || User::query()->where('email', 'admin@example.com')->doesntExist()) {
            $this->components->error('Az alapseeder smoke nem hozta létre a kötelező rendszeradatokat.');

            return self::FAILURE;
        }

        $this->components->info('A migration round-trip és az idempotens alapseeder smoke sikeres.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, bool|string>  $parameters
     */
    private function executeArtisanCommand(string $command, array $parameters): bool
    {
        $exitCode = Artisan::call($command, $parameters, $this->output);

        if ($exitCode !== self::SUCCESS) {
            $this->components->error("A(z) {$command} parancs hibával állt le.");

            return false;
        }

        return true;
    }
}
