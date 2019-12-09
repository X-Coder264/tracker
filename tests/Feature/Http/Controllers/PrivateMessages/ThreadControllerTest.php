<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\PrivateMessages;

use App\Models\PrivateMessages\Thread;
use App\Models\PrivateMessages\ThreadMessage;
use App\Models\PrivateMessages\ThreadParticipant;
use App\Models\User;
use Carbon\Carbon;
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

        $user = factory(User::class)->create();
        $irrelevantUser = factory(User::class)->create();
        $threadOne = factory(Thread::class)->create(['subject' => 'test 1']);
        $threadTwo = factory(Thread::class)->create(['user_id' => $irrelevantUser->id]);
        $threadThree = factory(Thread::class)->create(['subject' => 'test 3']);

        factory(ThreadParticipant::class)->create(
            [
                'user_id' => $user->id,
                'thread_id' => $threadOne->id,
                'last_read_at' => Carbon::now()->subMinutes(2),
            ]
        );

        factory(ThreadParticipant::class)->create(
            [
                'user_id' => $user->id,
                'thread_id' => $threadThree->id,
                'last_read_at' => Carbon::now()->addMinutes(2),
            ]
        );

        factory(ThreadParticipant::class)->create(
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

        $user = factory(User::class)->create();
        $thread = factory(Thread::class)->create(['subject' => 'test 1']);

        $participant = factory(ThreadParticipant::class)->create(
            [
                'user_id' => $user->id,
                'thread_id' => $thread->id,
                'last_read_at' => null,
            ]
        );

        factory(ThreadParticipant::class)->create(['thread_id' => $thread->id]);

        $messageOne = factory(ThreadMessage::class)->create(['thread_id' => $thread->id, 'user_id' => $user->id]);
        $messageTwo = factory(ThreadMessage::class)->create(['thread_id' => $thread->id]);
        factory(ThreadMessage::class)->create();

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
        $user = factory(User::class)->create();
        $thread = factory(Thread::class)->create(['subject' => 'test 1']);

        factory(ThreadParticipant::class)->create(['thread_id' => $thread->id]);

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
        $thread = factory(Thread::class)->create();
        $response = $this->get(route('threads.show', $thread));
        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }
}
