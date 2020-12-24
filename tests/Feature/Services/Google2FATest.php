<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use PragmaRX\Google2FAQRCode\Google2FA;
use PragmaRX\Google2FAQRCode\QRCode\Chillerlan;
use Tests\TestCase;

final class Google2FATest extends TestCase
{
    public function testItInstantiatesWithChillerlanQRCodeProvider(): void
    {
        /** @var Google2FA $google2FA */
        $google2FA = $this->app->make(Google2FA::class);

        $this->assertInstanceOf(Google2FA::class, $google2FA);

        $this->assertInstanceOf(Chillerlan::class, $google2FA->getQrCodeService());
    }
}
