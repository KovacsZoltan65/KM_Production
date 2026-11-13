<?php

namespace App\Support\Testing;

use Illuminate\Contracts\Config\Repository;

final class TestDiagnostics
{
    public static function configure(Repository $config): void
    {
        if ($config->get('app.env') !== 'testing') {
            return;
        }

        foreach (array_keys($config->get('database.connections', [])) as $name) {
            $config->set("database.connections.{$name}.mask_bindings_in_exception_messages", true);
        }
    }
}
