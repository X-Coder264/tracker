<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Invite;
use App\Models\Locale;
use App\Models\News;
use App\Models\PrivateMessages\Thread;
use App\Models\Snatch;
use App\Models\Torrent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Facades\App\Services\SizeFormatter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class UserTest extends TestCase
{
    use DatabaseTransactions;

    public function testUserHasSlug(): void
    {
        $locale = factory(Locale::class)->create();
        $user = new User();
        $user->email = 'test@gmail.com';
        $user->name = 'test name';
        $user->password = 'test test';
        $user->locale_id = $locale->id;
        $user->timezone = 'Europe/Zagreb';
        $user->save();

        $this->assertNotEmpty($user->slug);
    }

    public function testUserHasPasskey(): void
    {
        $locale = factory(Locale::class)->create();
        $user = new User();
        $user->email = 'test@gmail.com';
        $user->name = 'test name';
        $user->password = 'test test';
        $user->locale_id = $locale->id;
        $user->timezone = 'Europe/Zagreb';
        $user->save();

        $this->assertNotEmpty($user->passkey);
        $this->assertSame(64, strlen($user->passkey));
    }

    public function testUploadedAccessor(): void
    {
        factory(User::class)->create();
        $user = User::firstOrFail();
        $returnValue = '500 MB';
        SizeFormatter::shouldReceive('getFormattedSize')->once()->with($user->getOriginal('uploaded'))->andReturn($returnValue);
        $this->assertSame($returnValue, $user->uploaded);
    }

    public function testDownloadedAccessor(): void
    {
        factory(User::class)->create();
        $user = User::firstOrFail();
        $returnValue = '500 MB';
        SizeFormatter::shouldReceive('getFormattedSize')->once()->with($user->getOriginal('downloaded'))->andReturn($returnValue);
        $this->assertSame($returnValue, $user->downloaded);
    }

    public function testTorrentRelationship(): void
    {
        factory(Torrent::class)->create();

        $user = User::firstOrFail();
        $torrent = Torrent::firstOrFail();
        $this->assertInstanceOf(HasMany::class, $user->torrents());
        $this->assertInstanceOf(Collection::class, $user->torrents);
        $this->assertSame($user->torrents[0]->id, $torrent->id);
        $this->assertSame($user->torrents[0]->name, $torrent->name);
        $this->assertSame($user->torrents[0]->size, $torrent->size);
        $this->assertSame($user->torrents[0]->info_hash, $torrent->info_hash);
    }

    public function testLanguageRelationship(): void
    {
        $user = factory(User::class)->create();

        $freshUser = $user->fresh();
        $this->assertInstanceOf(BelongsTo::class, $freshUser->language());
        $this->assertInstanceOf(Locale::class, $freshUser->language);
        $this->assertSame($user->language->id, $freshUser->language->id);
    }

    public function testSnatchesRelationship(): void
    {
        factory(Snatch::class)->states('snatched')->create();

        $user = User::latest('id')->firstOrFail();
        $snatch = Snatch::firstOrFail();
        $this->assertInstanceOf(HasMany::class, $user->snatches());
        $this->assertInstanceOf(Collection::class, $user->snatches);
        $this->assertSame($user->snatches[0]->id, $snatch->id);
        $this->assertSame($user->snatches[0]->torrent_id, $snatch->torrent_id);
        $this->assertSame($user->snatches[0]->uploaded, $snatch->uploaded);
        $this->assertSame($user->snatches[0]->downloaded, $snatch->downloaded);
        $this->assertSame($user->snatches[0]->left, $snatch->left);
        $this->assertSame($user->snatches[0]->seedTime, $snatch->seedTime);
        $this->assertSame($user->snatches[0]->leechTime, $snatch->leechTime);
        $this->assertSame($user->snatches[0]->timesAnnounced, $snatch->timesAnnounced);
        $this->assertSame($user->snatches[0]->userAgent, $snatch->userAgent);
        $this->assertSame($user->snatches[0]->seeder, $snatch->seeder);
        $this->assertSame($user->snatches[0]->created_at->format('Y-m-d H:i:s'), $snatch->created_at->format('Y-m-d H:i:s'));
        $this->assertSame($user->snatches[0]->updated_at->format('Y-m-d H:i:s'), $snatch->updated_at->format('Y-m-d H:i:s'));
        $this->assertSame($user->snatches[0]->finished_at->format('Y-m-d H:i:s'), $snatch->finished_at->format('Y-m-d H:i:s'));
    }

    public function testThreadsRelationship(): void
    {
        factory(Thread::class)->create();

        $user = User::firstOrFail();
        $thread = Thread::firstOrFail();
        $this->assertInstanceOf(HasMany::class, $user->threads());
        $this->assertInstanceOf(Collection::class, $user->threads);
        $this->assertTrue($user->threads[0]->is($thread));
    }

    public function testNewsRelationship(): void
    {
        factory(News::class)->create();

        $user = User::firstOrFail();
        $news = News::firstOrFail();
        $this->assertInstanceOf(HasMany::class, $user->news());
        $this->assertInstanceOf(Collection::class, $user->news);
        $this->assertTrue($user->news[0]->is($news));
    }

    public function testInviterRelationshipWhenTheInviterExists(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var User $invitedUser */
        $invitedUser = factory(User::class)->create(['inviter_user_id' => $user->id]);

        $this->assertInstanceOf(HasOne::class, $invitedUser->inviter());
        $this->assertInstanceOf(User::class, $invitedUser->inviter);
        $this->assertTrue($invitedUser->inviter->is($user));
    }

    public function testInviterRelationshipWhenTheInviterDoesNotExist(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();

        $this->assertInstanceOf(HasOne::class, $user->inviter());
        $this->assertNull($user->inviter);
    }

    public function testInvitesRelationship(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();

        /** @var Invite[] $invites */
        $invites = factory(Invite::class, 2)->create(['user_id' => $user->id]);

        $this->assertInstanceOf(HasMany::class, $user->invites());
        $this->assertInstanceOf(Collection::class, $user->invites);
        $this->assertTrue($invites[0]->is($user->invites[0]));
        $this->assertTrue($invites[1]->is($user->invites[1]));
    }

    public function testLastSeenAtAtAttributeIsCastedToCarbon(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();

        $this->assertInstanceOf(CarbonImmutable::class, $user->last_seen_at);
    }

    public function testInviteesRelationship(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();

        /** @var User[] $users */
        $users = factory(User::class, 2)->create(['inviter_user_id' => $user->id]);

        $this->assertInstanceOf(HasMany::class, $user->invitees());
        $this->assertInstanceOf(Collection::class, $user->invitees);
        $this->assertTrue($users[0]->is($user->invitees[0]));
        $this->assertTrue($users[1]->is($user->invitees[1]));
    }

    public function testAUserGets2FASecretKeyWhenCreating(): void
    {
        $locale = factory(Locale::class)->create();

        $user = new User();
        $user->email = 'test@gmail.com';
        $user->name = 'test name';
        $user->password = 'test test';
        $user->locale_id = $locale->id;
        $user->timezone = 'Europe/Zagreb';
        $user->save();

        $this->assertNotEmpty($user->two_factor_secret_key);
        $this->assertSame(32, strlen($user->two_factor_secret_key));
    }

    public function test2FAIsDisabledByDefault(): void
    {
        $locale = factory(Locale::class)->create();

        $user = new User();
        $user->email = 'test@gmail.com';
        $user->name = 'test name';
        $user->password = 'test test';
        $user->locale_id = $locale->id;
        $user->timezone = 'Europe/Zagreb';
        $user->save();

        $user->refresh();

        $this->assertFalse($user->is_two_factor_enabled);
    }
}
