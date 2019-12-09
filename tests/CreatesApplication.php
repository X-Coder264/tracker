<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        /** @var Repository $cacheRepository */
        $cacheRepository = $app->make(Repository::class);
        // delete all cache before each test
        $cacheRepository->getStore()->flush();

        return $app;
    }
}
