<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        Hash::swap(new HashFake());

        // delete all cache before each test
        Cache::flush();

        return $app;
    }
}
