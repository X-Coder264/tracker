<?php

declare(strict_types=1);

namespace Tests\Feature\Models\PrivateMessages;

use App\Models\PrivateMessages\Thread;
use App\Models\PrivateMessages\ThreadMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ThreadMessageTest extends TestCase
{
    use DatabaseTransactions;

    public function testUserRelationship(): void
    {
        factory(ThreadMessage::class)->create();

        $user = User::firstOrFail();
        $message = ThreadMessage::firstOrFail();
        $this->assertInstanceOf(BelongsTo::class, $message->user());
        $this->assertInstanceOf(User::class, $message->user);
        $this->assertTrue($message->user->is($user));
    }

    public function testThreadRelationship(): void
    {
        factory(ThreadMessage::class)->create();

        $thread = Thread::firstOrFail();
        $message = ThreadMessage::firstOrFail();
        $this->assertInstanceOf(BelongsTo::class, $message->thread());
        $this->assertInstanceOf(Thread::class, $message->thread);
        $this->assertTrue($message->thread->is($thread));
    }
}
