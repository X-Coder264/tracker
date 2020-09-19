<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\PrivateMessages;

use Carbon\Carbon;
use Database\Factories\ThreadFactory;
use Database\Factories\ThreadMessageFactory;
use Database\Factories\ThreadParticipantFactory;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ThreadControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testIndex(): void
    {
        $this->withoutExceptionHandling();

        $user = UserFactory::new()->create();
        $irrelevantUser = UserFactory::new()->create();
        $threadOne = ThreadFactory::new()->create(['subject' => 'test 1']);
        $threadTwo = ThreadFactory::new()->create(['user_id' => $irrelevantUser->id]);
        $threadThree = ThreadFactory::new()->create(['subject' => 'test 3']);

        ThreadParticipantFactory::new()->create(
            [
                'user_id' => $user->id,
                'thread_id' => $threadOne->id,
                'last_read_at' => Carbon::now()->subMinutes(2),
            ]
        );

        ThreadParticipantFactory::new()->create(
            [
                'user_id' => $user->id,
                'thread_id' => $threadThree->id,
                'last_read_at' => Carbon::now()->addMinutes(2),
            ]
        );

        ThreadParticipantFactory::new()->create(
            [
                'user_id' => $irrelevantUser->id,
                'thread_id' => $threadTwo->id,
            ]
        );

        $this->actingAs($user);

        $response = $this->get(route('threads.index'));
        $response->assertStatus(200);
        $response->assertViewIs('private-messages.thread-index');

        $response->assertViewHas('threads');
        $response->assertViewHas('user', $user);
        $response->assertViewHas('unreadThreads');
        $response->assertViewHas('timezone');

        $threads = $response->viewData('threads');

        $this->assertSame(2, $threads->count());

        $response->assertSee($threads[0]->subject);
        $response->assertSee($threads[1]->subject);

        $this->assertTrue($threads->contains($threadOne->id));
        $this->assertTrue($threads->contains($threadThree->id));

        $this->assertSame(1, $response->viewData('unreadThreads')->count());
        $this->assertTrue($response->viewData('unreadThreads')->contains($threadOne->id));
    }

    public function testShow(): void
    {
        $this->withoutExceptionHandling();

        $user = UserFactory::new()->create();
        $thread = ThreadFactory::new()->create(['subject' => 'test 1']);

        $participant = ThreadParticipantFactory::new()->create(
            [
                'user_id' => $user->id,
                'thread_id' => $thread->id,
                'last_read_at' => null,
            ]
        );

        ThreadParticipantFactory::new()->create(['thread_id' => $thread->id]);

        $messageOne = ThreadMessageFactory::new()->create(['thread_id' => $thread->id, 'user_id' => $user->id]);
        $messageTwo = ThreadMessageFactory::new()->create(['thread_id' => $thread->id]);
        ThreadMessageFactory::new()->create();

        $cache = $this->app->make(Repository::class);
        $cache->put(
            sprintf('user.%d.unreadThreads', $user->id),
            new Collection([$thread->id]),
            33
        );

        $this->actingAs($user);

        $response = $this->get(route('threads.show', $thread));
        $response->assertStatus(200);
        $response->assertViewIs('private-messages.thread-show');
        $response->assertViewHas('thread', $thread);
        $response->assertViewHas('messages');
        $response->assertViewHas('participantsText');

        $messages = $response->viewData('messages');
        $this->assertInstanceOf(LengthAwarePaginator::class, $messages);
        $this->assertTrue($messages[0]->is($messageOne));
        $this->assertTrue($messages[1]->is($messageTwo));
        $this->assertSame(2, $messages->total());

        $this->assertEmpty($cache->get(sprintf('user.%d.unreadThreads', $user->id)));

        $freshParticipant = $participant->fresh();
        $this->assertLessThan(5, Carbon::now()->diffInSeconds($freshParticipant->last_read_at));
    }

    public function testShowThreadWhenTheUserIsNotAParticipant(): void
    {
        $user = UserFactory::new()->create();
        $thread = ThreadFactory::new()->create(['subject' => 'test 1']);

        ThreadParticipantFactory::new()->create(['thread_id' => $thread->id]);

        $cache = $this->app->make(Repository::class);
        $cache->put(
            sprintf('user.%d.unreadThreads', $user->id),
            new Collection([555]),
            33
        );

        $this->actingAs($user);

        $response = $this->get(route('threads.show', $thread));
        $response->assertStatus(404);

        $threads = $cache->get(sprintf('user.%d.unreadThreads', $user->id));
        $this->assertTrue($threads->contains(555));
    }

    public function testGuestsCannotSeeTheThread(): void
    {
        $thread = ThreadFactory::new()->create();
        $response = $this->get(route('threads.show', $thread));
        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }
}
