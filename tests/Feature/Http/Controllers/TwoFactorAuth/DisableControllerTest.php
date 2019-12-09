<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\TwoFactorAuth;

use App\Models\User;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class DisableControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testDisable(): void
    {
        $this->withoutExceptionHandling();

        /** @var User $user */
        $user = factory(User::class)->states('2fa_enabled')->create();

        $this->actingAs($user);

        $urlGenerator = $this->app->make(UrlGenerator::class);
        $response = $this->post($urlGenerator->route('2fa.disable'));

        $response->assertStatus(302);
        $response->assertRedirect($urlGenerator->route('2fa.status'));
        $response->assertSessionHas(
            'success',
            $this->app->make(Translator::class)->get('messages.2fa.successfully_disabled.message')
        );

        $user->refresh();

        $this->assertFalse($user->is_two_factor_enabled);
    }
}
