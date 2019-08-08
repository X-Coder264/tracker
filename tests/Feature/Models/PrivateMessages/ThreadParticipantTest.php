<?php

declare(strict_types=1);

namespace Tests\Feature\Models\PrivateMessages;

use Tests\TestCase;
use App\Models\User;
use Carbon\CarbonImmutable;
use App\Models\PrivateMessages\Thread;
use App\Models\PrivateMessages\ThreadParticipant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ThreadParticipantTest extends TestCase
{
    use DatabaseTransactions;

    public function testLastReadAtIsCastToCarbon(): void
    {
        $participant = factory(ThreadParticipant::class)->states('readTheThread')->create();
        $this->assertInstanceOf(CarbonImmutable::class, $participant->last_read_at);
    }

    public function testUserRelationship(): void
    {
        factory(ThreadParticipant::class)->create();

        $user = User::firstOrFail();
        $participant = ThreadParticipant::firstOrFail();
        $this->assertInstanceOf(BelongsTo::class, $participant->user());
        $this->assertInstanceOf(User::class, $participant->user);
        $this->assertTrue($participant->user->is($user));
    }

    public function testThreadRelationship(): void
    {
        factory(ThreadParticipant::class)->create();

        $thread = Thread::firstOrFail();
        $participant = ThreadParticipant::firstOrFail();
        $this->assertInstanceOf(BelongsTo::class, $participant->thread());
        $this->assertInstanceOf(Thread::class, $participant->thread);
        $this->assertTrue($participant->thread->is($thread));
    }
}
