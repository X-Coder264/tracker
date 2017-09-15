<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Cache\Repository;

trait CreatesApplication
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        Hash::swap(new HashFake());

        /** @var Repository $cacheRepository */
        $cacheRepository = $app->make(Repository::class);
        // delete all cache before each test
        $cacheRepository->getStore()->flush();

        return $app;
    }
}
