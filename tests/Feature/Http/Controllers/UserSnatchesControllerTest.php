<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Snatch;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserSnatchesControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testShow(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        $nonRelevantUser = factory(User::class)->create();

        $snatches = factory(Snatch::class, 2)->states('snatched')->create(['user_id' => $user->id]);
        factory(Snatch::class)->create(['user_id' => $user->id, 'left' => 500]);
        factory(Snatch::class, 2)->create(['user_id' => $nonRelevantUser->id]);

        $this->actingAs($user);

        $response = $this->get(route('user-snatches.show', $user));
        $response->assertStatus(200);
        $response->assertViewIs('user-snatches.show');
        $response->assertViewHas(['snatches', 'user']);

        $this->assertTrue($user->is($response->viewData('user')));
        $this->assertInstanceOf(LengthAwarePaginator::class, $response->viewData('snatches'));
        $this->assertSame(2, $response->viewData('snatches')->count());
        $this->assertSame($user->torrents_per_page, $response->viewData('snatches')->perPage());
        $this->assertTrue($snatches[1]->is($response->viewData('snatches')[0]));
        $this->assertTrue($snatches[0]->is($response->viewData('snatches')[1]));

        $response->assertSee($snatches[0]->torrent->name);
        $response->assertSee($snatches[1]->torrent->name);
        $response->assertSee($snatches[0]->uploaded);
        $response->assertSee($snatches[1]->uploaded);
        $response->assertSee($snatches[0]->downloaded);
        $response->assertSee($snatches[1]->downloaded);
        $response->assertSee($snatches[0]->seed_time);
        $response->assertSee($snatches[1]->seed_time);
        $response->assertSee($snatches[0]->leech_time);
        $response->assertSee($snatches[1]->leech_time);
        $response->assertSee($snatches[0]->user_agent);
        $response->assertSee($snatches[1]->user_agent);
        $response->assertSee($snatches[0]->finished_at->timezone($user->timezone));
        $response->assertSee($snatches[1]->finished_at->timezone($user->timezone));
    }

    public function testGuestGetsRedirectedToTheLoginPageWhenTryingToAccessTheSnatchesPage(): void
    {
        $user = factory(User::class)->create();

        factory(Snatch::class)->create(['user_id' => $user->id]);

        $response = $this->get(route('user-snatches.show', $user));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }
}
