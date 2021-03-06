<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\TwoFactorAuth;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PragmaRX\Google2FAQRCode\Google2FA;
use Tests\TestCase;

final class StatusControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testStatusPageWhenUser2FAIsDisabled(): void
    {
        $this->withoutExceptionHandling();

        /** @var User $user */
        $user = UserFactory::new()->twoFactorAuthDisabled()->create();

        $this->actingAs($user);

        $barCode = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAADIEAQAAABXwbpWAAAABGdBTUEAALGPC/xh';

        $google2FA = $this->createMock(Google2FA::class);
        $google2FA->expects($this->once())
            ->method('getQRCodeInline')
            ->with(
                $this->app->make(Repository::class)->get('app.name'),
                $user->email,
                $user->two_factor_secret_key
            )
            ->willReturn($barCode);

        $this->app->instance(Google2FA::class, $google2FA);

        $response = $this->get($this->app->make(UrlGenerator::class)->route('2fa.status'));
        $response->assertOk();
        $response->assertViewIs('2fa.status');
        $response->assertViewHas('user', $user);
        $response->assertViewHas('barcode', $barCode);

        $response->assertSee(sprintf('<img src="%s" alt="" style="max-height: 250px"/>', $barCode), false);

        $translator = $this->app->make(Translator::class);

        $response->assertSee($translator->get('messages.2fa.info_message'));
        $response->assertSee($translator->get('messages.2fa.please_scan_barcode_message'), false);
        $response->assertSee($translator->get('messages.2fa.use_secret_key_message', ['code' => $user->two_factor_secret_key]), false);
        $response->assertSee($translator->get('messages.2fa.enable_button.caption'));
        $response->assertDontSee($translator->get('messages.2fa.disable_button.caption'));
    }

    public function testStatusPageWhenUser2FAIsEnabled(): void
    {
        $this->withoutExceptionHandling();

        /** @var User $user */
        $user = UserFactory::new()->twoFactorAuthEnabled()->create();

        $this->actingAs($user);

        $google2FA = $this->createMock(Google2FA::class);
        $google2FA->expects($this->never())->method('getQRCodeInline');

        $this->app->instance(Google2FA::class, $google2FA);

        $response = $this->get($this->app->make(UrlGenerator::class)->route('2fa.status'));
        $response->assertOk();
        $response->assertViewIs('2fa.status');
        $response->assertViewHas('user', $user);
        $response->assertViewHas('barcode', null);

        $translator = $this->app->make(Translator::class);

        $response->assertSee($translator->get('messages.2fa.info_message'));
        $response->assertDontSee($translator->get('messages.2fa.please_scan_barcode_message'));
        $response->assertDontSee($translator->get('messages.2fa.use_secret_key_message', ['code' => $user->two_factor_secret_key]));
        $response->assertDontSee($translator->get('messages.2fa.enable_button.caption'));
        $response->assertSee($translator->get('messages.2fa.disable_button.caption'));
    }

    public function testStatusPageAsGuest(): void
    {
        $urlGenerator = $this->app->make(UrlGenerator::class);

        $google2FA = $this->createMock(Google2FA::class);
        $google2FA->expects($this->never())->method('getQRCodeInline');

        $this->app->instance(Google2FA::class, $google2FA);

        $response = $this->get($urlGenerator->route('2fa.status'));

        $response->assertStatus(302);
        $response->assertRedirect($urlGenerator->route('login'));
    }
}
