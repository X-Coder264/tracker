<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        Hash::swap(new HashFake());

        // delete all cache before each test
        $app->make('cache')->flush();

        return $app;
    }
}
