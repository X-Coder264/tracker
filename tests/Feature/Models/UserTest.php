<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Locale;
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

    public function testPasswordMutator()
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

    public function testUserHasSlug()
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

    public function testTorrentRelationship()
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

    public function testLanguageRelationship()
    {
        $user = factory(User::class)->create();

        $freshUser = $user->fresh();
        $this->assertInstanceOf(BelongsTo::class, $freshUser->language());
        $this->assertInstanceOf(Locale::class, $freshUser->language);
        $this->assertSame($user->language->id, $freshUser->language->id);
    }
}
