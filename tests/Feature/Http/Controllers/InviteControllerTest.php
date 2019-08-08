<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Invite;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Testing\DatabaseTransactions;

final class InviteControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testCreate(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->states('has_available_invites')->create();
        factory(User::class)->create(['inviter_user_id' => $user->id, 'name' => 'foo bar name']);
        factory(Invite::class, 2)->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $urlGenerator = $this->app->make(UrlGenerator::class);

        $response = $this->get($urlGenerator->route('invites.create'));

        $response->assertOk();

        $response->assertViewHas('user', $user);
        $response->assertViewHas('timezone', $user->timezone);
        $response->assertViewHas('invites');
        $response->assertViewHas('invitees');

        /** @var Collection $invites */
        $invites = $response->original->gatherData()['invites'];
        $this->assertInstanceOf(Collection::class, $invites);
        $this->assertTrue($invites->contains($user->invites[0]));
        $this->assertTrue($invites->contains($user->invites[1]));

        /** @var Collection $invitees */
        $invitees = $response->original->gatherData()['invitees'];
        $this->assertInstanceOf(Collection::class, $invitees);
        $this->assertTrue($invitees->contains($user->invitees[0]));

        $response->assertSee($user->invites[0]->code);
        $response->assertSee($user->invites[1]->code);

        $response->assertSee($user->invitees[0]->name);

        $response->assertSee($this->app->make(Translator::class)->trans('messages.invites.create'));
    }

    public function testCreateAsANonLoggedInUser(): void
    {
        $urlGenerator = $this->app->make(UrlGenerator::class);

        $response = $this->get($urlGenerator->route('invites.create'));

        $response->assertRedirect($urlGenerator->route('login'));
        $response->assertStatus(302);
    }

    public function testStoreWhenTheUserHasAvailableInvites(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->states('has_available_invites')->create();

        $this->actingAs($user);

        $this->assertSame(0, Invite::count());

        $urlGenerator = $this->app->make(UrlGenerator::class);

        $response = $this->post($urlGenerator->route('invites.store'));

        $response->assertRedirect($urlGenerator->route('invites.create'));
        $response->assertStatus(302);

        $response->assertSessionHas(
            'success',
            $this->app->make(Translator::class)->transChoice('messages.invites_successfully_created_message', 3)
        );

        $this->assertSame(1, Invite::count());

        $freshUser = $user->fresh();

        $this->assertSame($user->invites_amount, $freshUser->invites_amount);

        $invite = Invite::first();

        $this->assertTrue($user->invites->contains($invite));

        $this->assertSame(40, strlen($invite->code));
        $this->assertLessThan(5, CarbonImmutable::now()->addDays(3)->diffInSeconds($invite->expires_at));
    }

    public function testStoreWhenTheUserDoesNotHaveAvailableInvites(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->states('has_no_available_invites')->create();

        $this->actingAs($user);

        $this->assertSame(0, Invite::count());

        $urlGenerator = $this->app->make(UrlGenerator::class);

        $response = $this->post($urlGenerator->route('invites.store'));

        $response->assertRedirect($urlGenerator->route('invites.create'));
        $response->assertStatus(302);

        $response->assertSessionHas(
            'error',
            $this->app->make(Translator::class)->trans('messages.invites_no_invites_left_error_message')
        );

        $this->assertSame(0, Invite::count());

        $user->fresh();
        $this->assertSame(0, $user->invites_amount);
    }

    public function testStoreAsANonLoggedInUser(): void
    {
        $urlGenerator = $this->app->make(UrlGenerator::class);

        $response = $this->post($urlGenerator->route('invites.store'));

        $response->assertRedirect($urlGenerator->route('login'));
        $response->assertStatus(302);
    }
}
