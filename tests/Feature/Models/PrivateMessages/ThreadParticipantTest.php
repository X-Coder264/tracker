<?php

declare(strict_types=1);

namespace Tests\Feature\Models\PrivateMessages;

use App\Models\PrivateMessages\Thread;
use App\Models\PrivateMessages\ThreadParticipant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\ThreadParticipantFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ThreadParticipantTest extends TestCase
{
    use DatabaseTransactions;

    public function testLastReadAtIsCastToCarbon(): void
    {
        $participant = ThreadParticipantFactory::new()->readTheThread()->create();
        $this->assertInstanceOf(CarbonImmutable::class, $participant->last_read_at);
    }

    public function testUserRelationship(): void
    {
        ThreadParticipantFactory::new()->create();

        $user = User::firstOrFail();
        $participant = ThreadParticipant::firstOrFail();
        $this->assertInstanceOf(BelongsTo::class, $participant->user());
        $this->assertInstanceOf(User::class, $participant->user);
        $this->assertTrue($participant->user->is($user));
    }

    public function testThreadRelationship(): void
    {
        ThreadParticipantFactory::new()->create();

        $thread = Thread::firstOrFail();
        $participant = ThreadParticipant::firstOrFail();
        $this->assertInstanceOf(BelongsTo::class, $participant->thread());
        $this->assertInstanceOf(Thread::class, $participant->thread);
        $this->assertTrue($participant->thread->is($thread));
    }
}
