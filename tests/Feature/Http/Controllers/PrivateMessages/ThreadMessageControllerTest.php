<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\PrivateMessages;

use App\Models\PrivateMessages\Thread;
use App\Models\PrivateMessages\ThreadMessage;
use App\Models\PrivateMessages\ThreadParticipant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ThreadMessageControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testCreate(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        $thread = factory(Thread::class)->create();

        $this->actingAs($user);

        $response = $this->get(route('thread-messages.create', $thread));
        $response->assertStatus(200);
        $response->assertViewIs('private-messages.message-create');

        $response->assertViewHas('thread', $thread);
        $response->assertViewHas('threadMessage');

        $this->assertInstanceOf(ThreadMessage::class, $response->viewData('threadMessage'));
    }

    public function testGuestsCannotSeeTheCreatePage(): void
    {
        $thread = factory(Thread::class)->create();
        $response = $this->get(route('thread-messages.create', $thread));
        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function testStore(): void
    {
        $this->withoutExceptionHandling();

        $time = Carbon::now()->subDays(7);

        $user = factory(User::class)->create();
        $thread = factory(Thread::class)->create(['created_at' => $time, 'updated_at' => $time]);
        $threadParticipant = factory(ThreadParticipant::class)->create(
            [
                'user_id' => $user->id,
                'thread_id' => $thread->id,
                'created_at' => $time,
                'updated_at' => $time,
            ]
        );

        $anotherThreadParticipant = factory(ThreadParticipant::class)->create(['thread_id' => $thread->id]);

        $cache = $this->app->make(Repository::class);
        $cache->put(sprintf('user.%d.unreadThreads', $threadParticipant->user_id), 13, 30);
        $cache->put(sprintf('user.%d.unreadThreads', $anotherThreadParticipant->user_id), 99, 30);

        $this->actingAs($user);

        $message = 'test message';

        $response = $this->post(route('thread-messages.store', $thread), [
            'message' => $message,
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('threads.show', $thread));
        $response->assertSessionHas('success');

        $threadMessage = ThreadMessage::firstOrFail();
        $this->assertSame($user->id, (int) $threadMessage->user_id);
        $this->assertSame($thread->id, (int) $threadMessage->thread_id);
        $this->assertSame($message, $threadMessage->message);

        $freshThread = $thread->fresh();
        $this->assertLessThan(5, Carbon::now()->diffInSeconds($freshThread->updated_at));
        $this->assertSame($thread->created_at->format('Y-m-d H:i:s'), $freshThread->created_at->format('Y-m-d H:i:s'));

        $freshThreadParticipant = $threadParticipant->fresh();
        $this->assertLessThan(5, Carbon::now()->diffInSeconds($freshThreadParticipant->updated_at));
        $this->assertSame(
            $threadParticipant->created_at->format('Y-m-d H:i:s'),
            $freshThreadParticipant->created_at->format('Y-m-d H:i:s')
        );

        $freshAnotherThreadParticipant = $anotherThreadParticipant->fresh();
        $this->assertSame(
            $anotherThreadParticipant->created_at->format('Y-m-d H:i:s'),
            $freshAnotherThreadParticipant->created_at->format('Y-m-d H:i:s')
        );
        $this->assertSame(
            $anotherThreadParticipant->updated_at->format('Y-m-d H:i:s'),
            $freshAnotherThreadParticipant->updated_at->format('Y-m-d H:i:s')
        );

        $this->assertFalse($cache->has(sprintf('user.%d.unreadThreads', $threadParticipant->user_id)));
        $this->assertFalse($cache->has(sprintf('user.%d.unreadThreads', $anotherThreadParticipant->user_id)));
    }

    public function testOnlyThreadParticipantCanPostNewMessageToTheThread(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        $thread = factory(Thread::class)->create();
        factory(ThreadParticipant::class)->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $message = 'test message';

        $response = $this->post(route('thread-messages.store', $thread), [
            'message' => $message,
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('threads.index'));
        $response->assertSessionHas('warning');

        $this->assertSame(0, ThreadMessage::count());

        $freshThread = $thread->fresh();
        $this->assertSame($thread->created_at->format('Y-m-d H:i:s'), $freshThread->created_at->format('Y-m-d H:i:s'));
        $this->assertSame($thread->updated_at->format('Y-m-d H:i:s'), $freshThread->updated_at->format('Y-m-d H:i:s'));
    }
}
