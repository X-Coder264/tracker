<?php

declare(strict_types=1);

namespace Tests\Feature\Models\PrivateMessages;

use Tests\TestCase;
use App\Models\User;
use App\Models\PrivateMessages\Thread;
use Illuminate\Database\Eloquent\Collection;
use App\Models\PrivateMessages\ThreadMessage;
use App\Models\PrivateMessages\ThreadParticipant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThreadTest extends TestCase
{
    use RefreshDatabase;

    public function testThreadHasSlug(): void
    {
        $user = factory(User::class)->create();

        $thread = new Thread();
        $thread->subject = 'test';
        $thread->user_id = $user->id;
        $thread->save();

        $this->assertNotEmpty($thread->slug);
    }

    public function testCreatorRelationship(): void
    {
        factory(Thread::class)->create();

        $user = User::firstOrFail();
        $thread = Thread::firstOrFail();
        $this->assertInstanceOf(BelongsTo::class, $thread->creator());
        $this->assertInstanceOf(User::class, $thread->creator);
        $this->assertTrue($thread->creator->is($user));
    }

    public function testParticipantsRelationship(): void
    {
        $thread = factory(Thread::class)->create();
        factory(ThreadParticipant::class)->create(['thread_id' => $thread->id]);

        $thread = Thread::firstOrFail();
        $participant = ThreadParticipant::firstOrFail();
        $this->assertInstanceOf(HasMany::class, $thread->participants());
        $this->assertInstanceOf(Collection::class, $thread->participants);
        $this->assertTrue($thread->participants[0]->is($participant));
    }

    public function testMessagesRelationship(): void
    {
        $thread = factory(Thread::class)->create();
        factory(ThreadMessage::class)->create(['thread_id' => $thread->id]);

        $thread = Thread::firstOrFail();
        $message = ThreadMessage::firstOrFail();
        $this->assertInstanceOf(HasMany::class, $thread->messages());
        $this->assertInstanceOf(Collection::class, $thread->messages);
        $this->assertTrue($thread->messages[0]->is($message));
    }
}
