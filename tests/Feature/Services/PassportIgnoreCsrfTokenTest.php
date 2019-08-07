<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use Tests\TestCase;
use Laravel\Passport\Passport;

final class PassportIgnoreCsrfTokenTest extends TestCase
{
    public function testCsrfTokenIsIgnoredByPassport(): void
    {
        $this->assertTrue(Passport::$ignoreCsrfToken);
    }
}
