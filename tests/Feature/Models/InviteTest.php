<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Invite;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\DatabaseTransactions;

final class InviteTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    public function testUserRelationship(): void
    {
        $user = factory(User::class)->create();

        $invite = new Invite();
        $invite->code = $this->faker->unique()->text(255);
        $invite->user_id = $user->id;
        $invite->expires_at = CarbonImmutable::now();
        $invite->save();

        $this->assertInstanceOf(BelongsTo::class, $invite->user());
        $this->assertTrue($invite->user->is($user));
    }

    public function testExpiresAtAttributeIsCastedToCarbon(): void
    {
        /** @var Invite $invite */
        $invite = factory(Invite::class)->create();

        $this->assertInstanceOf(CarbonImmutable::class, $invite->expires_at);
    }
}