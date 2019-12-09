<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

final class TwoFactorStepControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testShowAsANonLoggedInUser(): void
    {
        $this->withoutExceptionHandling();

        $response = $this->get($this->app->make(UrlGenerator::class)->route('2fa.show_form'));
        $response->assertOk();
        $response->assertViewIs('auth.2fa_login_step');
    }

    public function testShowAsALoggedInUser(): void
    {
        $this->withoutExceptionHandling();

        /** @var User $user */
        $user = factory(User::class)->create();

        $this->actingAs($user);

        $urlGenerator = $this->app->make(UrlGenerator::class);

        $response = $this->get($urlGenerator->route('2fa.show_form'));
        $response->assertStatus(302);
        $response->assertRedirect($urlGenerator->route('home'));
    }

    public function testSuccessfulVerification(): void
    {
        $this->withoutExceptionHandling();

        /** @var User $user */
        $user = factory(User::class)->create(['remember_token' => null]);

        $secretCode = 'test foo';

        $google2FA = $this->createMock(Google2FA::class);
        $google2FA->expects($this->once())
            ->method('verifyKey')
            ->with($user->two_factor_secret_key, $secretCode)
            ->willReturn(true);

        $this->app->instance(Google2FA::class, $google2FA);

        $session = $this->app->make(Session::class);

        $session->put('2fa_user_id', $this->app->make(Encrypter::class)->encrypt($user->id));
        $session->put('2fa_remember_me', true);

        $urlGenerator = $this->app->make(UrlGenerator::class);
        $response = $this->post(
            $urlGenerator->route('2fa.verify'),
            [
                'code' => $secretCode,
            ]
        );

        $response->assertStatus(302);
        $response->assertRedirect($urlGenerator->route('home'));
        $response->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);

        $user->refresh();

        $this->assertNotEmpty($user->remember_token);
        $this->assertSame(60, strlen($user->remember_token));
        $response->assertCookie($this->app->make(Guard::class)->getRecallerName());

        $response->assertSessionMissing('2fa_user_id');
        $response->assertSessionMissing('2fa_remember_me');
    }

    public function testUnsuccessfulVerification(): void
    {
        $this->withoutExceptionHandling();

        /** @var User $user */
        $user = factory(User::class)->create(['remember_token' => null]);

        $secretCode = 'test foo';

        $google2FA = $this->createMock(Google2FA::class);
        $google2FA->expects($this->once())
            ->method('verifyKey')
            ->with($user->two_factor_secret_key, $secretCode)
            ->willReturn(false);

        $this->app->instance(Google2FA::class, $google2FA);

        $session = $this->app->make(Session::class);

        $encryptedUserId = $this->app->make(Encrypter::class)->encrypt($user->id);

        $session->put('2fa_user_id', $encryptedUserId);
        $session->put('2fa_remember_me', true);

        $urlGenerator = $this->app->make(UrlGenerator::class);
        $response = $this->post(
            $urlGenerator->route('2fa.verify'),
            [
                'code' => $secretCode,
            ]
        );

        $response->assertStatus(302);
        $response->assertRedirect($urlGenerator->route('2fa.show_form'));
        $response->assertSessionHasErrors(
            'code',
            $this->app->make(Translator::class)->get('validation.valid_two_factor_code')
        );
        $response->assertSessionHasInput('code', $secretCode);

        $this->assertGuest();

        $user->refresh();

        $this->assertEmpty($user->remember_token);
        $response->assertCookieMissing($this->app->make(Guard::class)->getRecallerName());

        $response->assertSessionHas('2fa_user_id', $encryptedUserId);
        $response->assertSessionHas('2fa_remember_me', true);
    }

    public function testVerificationRedirectsBackToFirstLoginStepWhenThereIsNoUserId(): void
    {
        $this->withoutExceptionHandling();

        $secretCode = 'test foo';

        $google2FA = $this->createMock(Google2FA::class);
        $google2FA->expects($this->never())->method('verifyKey');

        $this->app->instance(Google2FA::class, $google2FA);

        $urlGenerator = $this->app->make(UrlGenerator::class);
        $response = $this->post(
            $urlGenerator->route('2fa.verify'),
            [
                'code' => $secretCode,
            ]
        );

        $response->assertStatus(302);
        $response->assertRedirect($urlGenerator->route('login'));
        $response->assertSessionHasNoErrors();

        $this->assertGuest();

        $response->assertCookieMissing($this->app->make(Guard::class)->getRecallerName());

        $response->assertSessionMissing('2fa_user_id');
        $response->assertSessionMissing('2fa_remember_me');
    }

    public function testVerificationRedirectsBackToFirstLoginStepWhenTheUserDoesNotExistInTheDatabase(): void
    {
        $this->withoutExceptionHandling();

        /** @var User $user */
        $user = factory(User::class)->create();

        $secretCode = 'test foo';

        $google2FA = $this->createMock(Google2FA::class);
        $google2FA->expects($this->never())->method('verifyKey');

        $this->app->instance(Google2FA::class, $google2FA);

        $session = $this->app->make(Session::class);

        $encryptedUserId = $this->app->make(Encrypter::class)->encrypt($user->id + 1);

        $session->put('2fa_user_id', $encryptedUserId);

        $urlGenerator = $this->app->make(UrlGenerator::class);
        $response = $this->post(
            $urlGenerator->route('2fa.verify'),
            [
                'code' => $secretCode,
            ]
        );

        $response->assertStatus(302);
        $response->assertRedirect($urlGenerator->route('login'));
        $response->assertSessionHasNoErrors();

        $this->assertGuest();

        $response->assertCookieMissing($this->app->make(Guard::class)->getRecallerName());

        $response->assertSessionMissing('2fa_user_id');
        $response->assertSessionMissing('2fa_remember_me');
    }
}
