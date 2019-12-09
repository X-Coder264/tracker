<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\News;

use App\Models\News;
use App\Models\User;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Passport\Passport;
use Tests\AdminApiTestCase;

final class IndexEndpointTest extends AdminApiTestCase
{
    use DatabaseTransactions;

    public function testIndexWithAuthorInclude(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();

        Passport::actingAs($user);

        /** @var News[] $news */
        $news = factory(News::class, 2)->create();

        $response = $this->makeRequest(
            'GET',
            $this->app->make(UrlGenerator::class)->route(
                'admin.news.index',
                ['include' => 'author']
            )
        );

        $response->getResponse()->assertOk();

        $jsonResponse = $response->getJsonResponse();

        $this->assertSame(1, $jsonResponse['meta']['current-page']);
        $this->assertSame(15, $jsonResponse['meta']['per-page']);
        $this->assertSame(1, $jsonResponse['meta']['from']);
        $this->assertSame(2, $jsonResponse['meta']['to']);
        $this->assertSame(2, $jsonResponse['meta']['total']);
        $this->assertSame(1, $jsonResponse['meta']['last-page']);

        $this->assertSame(2, count($jsonResponse['data']));

        $this->assertSame('news', $jsonResponse['data'][0]['type']);
        $this->assertSame((string) $news[1]->id, $jsonResponse['data'][0]['id']);
        $this->assertSame($news[1]->subject, $jsonResponse['data'][0]['attributes']['subject']);
        $this->assertSame($news[1]->text, $jsonResponse['data'][0]['attributes']['text']);
        $this->assertSame($news[1]->created_at->toAtomString(), $jsonResponse['data'][0]['attributes']['created_at']);
        $this->assertSame($news[1]->updated_at->toAtomString(), $jsonResponse['data'][0]['attributes']['updated_at']);
        $this->assertSame('users', $jsonResponse['data'][0]['relationships']['author']['data']['type']);
        $this->assertSame((string) $news[1]->user_id, $jsonResponse['data'][0]['relationships']['author']['data']['id']);

        $this->assertSame('news', $jsonResponse['data'][1]['type']);
        $this->assertSame((string) $news[0]->id, $jsonResponse['data'][1]['id']);
        $this->assertSame($news[0]->subject, $jsonResponse['data'][1]['attributes']['subject']);
        $this->assertSame($news[0]->text, $jsonResponse['data'][1]['attributes']['text']);
        $this->assertSame($news[0]->created_at->toAtomString(), $jsonResponse['data'][1]['attributes']['created_at']);
        $this->assertSame($news[0]->updated_at->toAtomString(), $jsonResponse['data'][1]['attributes']['updated_at']);
        $this->assertSame('users', $jsonResponse['data'][1]['relationships']['author']['data']['type']);
        $this->assertSame((string) $news[0]->user_id, $jsonResponse['data'][1]['relationships']['author']['data']['id']);

        $this->assertSame('users', $jsonResponse['included'][0]['type']);
        $this->assertSame((string) $news[1]->user_id, $jsonResponse['included'][0]['id']);
        $this->assertSame($news[1]->author->name, $jsonResponse['included'][0]['attributes']['name']);

        $this->assertSame('users', $jsonResponse['included'][1]['type']);
        $this->assertSame((string) $news[0]->user_id, $jsonResponse['included'][1]['id']);
        $this->assertSame($news[0]->author->name, $jsonResponse['included'][1]['attributes']['name']);
    }

    public function testSubjectFilter(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();

        Passport::actingAs($user);

        /** @var News[] $news */
        $news = factory(News::class, 2)->create();

        $response = $this->makeRequest(
            'GET',
            $this->app->make(UrlGenerator::class)->route(
                'admin.news.index',
                ['include' => 'author', 'filter[subject]' => $news[0]->subject]
            )
        );

        $response->getResponse()->assertOk();

        $jsonResponse = $response->getJsonResponse();

        $this->assertSame(1, $jsonResponse['meta']['current-page']);
        $this->assertSame(15, $jsonResponse['meta']['per-page']);
        $this->assertSame(1, $jsonResponse['meta']['from']);
        $this->assertSame(1, $jsonResponse['meta']['to']);
        $this->assertSame(1, $jsonResponse['meta']['total']);
        $this->assertSame(1, $jsonResponse['meta']['last-page']);

        $this->assertSame(1, count($jsonResponse['data']));

        $this->assertSame('news', $jsonResponse['data'][0]['type']);
        $this->assertSame((string) $news[0]->id, $jsonResponse['data'][0]['id']);
        $this->assertSame($news[0]->subject, $jsonResponse['data'][0]['attributes']['subject']);
        $this->assertSame($news[0]->text, $jsonResponse['data'][0]['attributes']['text']);
        $this->assertSame($news[0]->created_at->toAtomString(), $jsonResponse['data'][0]['attributes']['created_at']);
        $this->assertSame($news[0]->updated_at->toAtomString(), $jsonResponse['data'][0]['attributes']['updated_at']);
        $this->assertSame('users', $jsonResponse['data'][0]['relationships']['author']['data']['type']);
        $this->assertSame((string) $news[0]->user_id, $jsonResponse['data'][0]['relationships']['author']['data']['id']);

        $this->assertSame('users', $jsonResponse['included'][0]['type']);
        $this->assertSame((string) $news[0]->user_id, $jsonResponse['included'][0]['id']);
        $this->assertSame($news[0]->author->name, $jsonResponse['included'][0]['attributes']['name']);
    }

    public function testAuthorFilter(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();

        Passport::actingAs($user);

        factory(News::class, 2)->create(['user_id' => $user->id]);

        $news = factory(News::class)->create();

        $response = $this->makeRequest(
            'GET',
            $this->app->make(UrlGenerator::class)->route(
                'admin.news.index',
                ['include' => 'author', 'filter[authorId]' => $news->user_id]
            )
        );

        $response->getResponse()->assertOk();

        $jsonResponse = $response->getJsonResponse();

        $this->assertSame(1, $jsonResponse['meta']['current-page']);
        $this->assertSame(15, $jsonResponse['meta']['per-page']);
        $this->assertSame(1, $jsonResponse['meta']['from']);
        $this->assertSame(1, $jsonResponse['meta']['to']);
        $this->assertSame(1, $jsonResponse['meta']['total']);
        $this->assertSame(1, $jsonResponse['meta']['last-page']);

        $this->assertSame(1, count($jsonResponse['data']));

        $this->assertSame('news', $jsonResponse['data'][0]['type']);
        $this->assertSame((string) $news->id, $jsonResponse['data'][0]['id']);
        $this->assertSame($news->subject, $jsonResponse['data'][0]['attributes']['subject']);
        $this->assertSame($news->text, $jsonResponse['data'][0]['attributes']['text']);
        $this->assertSame($news->created_at->toAtomString(), $jsonResponse['data'][0]['attributes']['created_at']);
        $this->assertSame($news->updated_at->toAtomString(), $jsonResponse['data'][0]['attributes']['updated_at']);
        $this->assertSame('users', $jsonResponse['data'][0]['relationships']['author']['data']['type']);
        $this->assertSame((string) $news->user_id, $jsonResponse['data'][0]['relationships']['author']['data']['id']);

        $this->assertSame('users', $jsonResponse['included'][0]['type']);
        $this->assertSame((string) $news->user_id, $jsonResponse['included'][0]['id']);
        $this->assertSame($news->author->name, $jsonResponse['included'][0]['attributes']['name']);
    }
}
