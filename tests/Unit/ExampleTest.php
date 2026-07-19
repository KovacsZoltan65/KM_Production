<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /** Ellenőrzi a Laravel konzol belépési pontjának meglétét. */
    public function test_artisan_entry_point_exists(): void
    {
        $this->assertFileExists(dirname(__DIR__, 2).'/artisan');
    }
}
