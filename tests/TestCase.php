<?php

namespace Tests;

use App\Support\Testing\TestEnvironmentGuard;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        TestEnvironmentGuard::assertLaravelConfiguration($app->make('config'));

        return $app;
    }
}
