<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\News;
use App\Models\User;
use Database\Factories\NewsFactory;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NewsTest extends TestCase
{
    use DatabaseTransactions;

    public function testNewsHasSlug(): void
    {
        $user = UserFactory::new()->create();
        $news = new News();
        $news->user_id = $user->id;
        $news->subject = 'test subject';
        $news->text = 'test text';
        $news->save();

        $this->assertNotEmpty($news->slug);
    }

    public function testAuthorRelationship(): void
    {
        NewsFactory::new()->create();

        $user = User::firstOrFail();
        $news = News::firstOrFail();
        $this->assertInstanceOf(BelongsTo::class, $news->author());
        $this->assertInstanceOf(User::class, $news->author);
        $this->assertTrue($user->is($news->author));
    }

    public function testAfterSavingNewsTheCacheGetsFlushed(): void
    {
        $cache = $this->app->make(Repository::class);
        $cache->put('news', [], 500);

        $this->assertTrue($cache->has('news'));

        $user = UserFactory::new()->create();

        $news = new News();
        $news->user_id = $user->id;
        $news->subject = 'test subject';
        $news->text = 'test text';
        $news->save();

        // the cache gets flushed when the news is saved
        $this->assertFalse($cache->has('news'));

        $cache->put('news', [], 500);

        $this->assertTrue($cache->has('news'));

        $news->subject = 'foo';
        $news->save();

        // the cache gets flushed when an existing news is saved
        $this->assertFalse($cache->has('news'));
    }
}
