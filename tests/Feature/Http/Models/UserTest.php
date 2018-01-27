<?php

namespace Tests\Feature\Http\Models;

use Tests\TestCase;
use App\Http\Models\User;
use App\Http\Models\Locale;
use App\Http\Models\Torrent;
use Illuminate\Support\Facades\Hash;
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

        factory(Locale::class)->create();
        $user = new User();
        $user->email = 'test@gmail.com';
        $user->name = 'test name';
        $user->password = $password;
        $user->locale_id = 1;
        $user->timezone = 'Europe/Zagreb';
        $user->save();

        $this->assertTrue(Hash::check($password, $user->password));
    }

    public function testUserHasSlug()
    {
        factory(Locale::class)->create();
        $user = new User();
        $user->email = 'test@gmail.com';
        $user->name = 'test name';
        $user->password = 'test test';
        $user->locale_id = 1;
        $user->timezone = 'Europe/Zagreb';
        $user->save();

        $this->assertNotEmpty($user->slug);
    }

    public function testTorrentRelationship()
    {
        factory(Torrent::class)->create();

        $user = User::findOrFail(1);
        $torrent = Torrent::findOrFail(1);
        $this->assertInstanceOf(HasMany::class, $user->torrents());
        $this->assertInstanceOf(Collection::class, $user->torrents);
        $this->assertSame($user->torrents[0]->id, $torrent->id);
        $this->assertSame($user->torrents[0]->name, $torrent->name);
        $this->assertSame($user->torrents[0]->size, $torrent->size);
        $this->assertSame($user->torrents[0]->infoHash, $torrent->infoHash);
    }

    public function testLanguageRelationship()
    {
        factory(User::class)->create();

        $user = User::findOrFail(1);
        $this->assertInstanceOf(BelongsTo::class, $user->language());
        $this->assertInstanceOf(Locale::class, $user->language);
        $this->assertSame(1, $user->language->id);
    }
}
