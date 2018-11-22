<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Locale;
use App\Models\Snatch;
use App\Models\Torrent;
use Illuminate\Support\Facades\Hash;
use Facades\App\Services\SizeFormatter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function testPasswordMutator(): void
    {
        $password = 'test password 123';

        $locale = factory(Locale::class)->create();
        $user = new User();
        $user->email = 'test@gmail.com';
        $user->name = 'test name';
        $user->password = $password;
        $user->locale_id = $locale->id;
        $user->timezone = 'Europe/Zagreb';
        $user->save();

        $this->assertTrue(Hash::check($password, $user->password));
    }

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
}
