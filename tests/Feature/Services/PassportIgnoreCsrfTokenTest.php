<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use Laravel\Passport\Passport;
use Tests\TestCase;

final class PassportIgnoreCsrfTokenTest extends TestCase
{
    public function testCsrfTokenIsIgnoredByPassport(): void
    {
        $this->assertTrue(Passport::$ignoreCsrfToken);
    }
}
