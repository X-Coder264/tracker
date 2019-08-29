<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\TwoFactorAuth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Testing\DatabaseTransactions;

final class EnableControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testEnable(): void
    {
        $this->withoutExceptionHandling();

        /** @var User $user */
        $user = factory(User::class)->states('2fa_disabled')->create();

        $this->actingAs($user);

        $urlGenerator = $this->app->make(UrlGenerator::class);
        $response = $this->post($urlGenerator->route('2fa.enable'));

        $response->assertStatus(302);
        $response->assertRedirect($urlGenerator->route('2fa.status'));
        $response->assertSessionHas(
            'success',
            $this->app->make(Translator::class)->trans('messages.2fa.successfully_enabled.message')
        );

        $user->refresh();

        $this->assertTrue($user->is_two_factor_enabled);
    }
}