<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\News;

use App\Models\News;
use App\Models\User;
use Tests\AdminApiTestCase;
use Illuminate\Http\Response;
use Laravel\Passport\Passport;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Foundation\Testing\DatabaseTransactions;

final class UpdateEndpointTest extends AdminApiTestCase
{
    use DatabaseTransactions;

    public function testUpdateWithAuthorInclude(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        Passport::actingAs($user);

        $news = factory(News::class)->create();
        $author = $news->author;

        $this->assertSame(1, News::count());

        $subject = 'test 111';
        $text = str_repeat('Important news!!!', 5);

        $data = [
            'data' => [
                'type' => 'news',
                'id' => (string) $news->id,
                'attributes' => [
                    'subject' => $subject,
                    'text' => $text,
                ],
            ],
        ];

        $response = $this->makeRequest(
            'PATCH',
            $this->app->make(UrlGenerator::class)->route(
                'admin.news.update',
                ['include' => 'author', 'record' => $news->id]
            ),
            $data
        );

        $response->getResponse()->assertOk();

        $this->assertSame(1, News::count());

        $jsonResponse = $response->getJsonResponse();

        $news->refresh();

        $this->assertSame($subject, $news->subject);
        $this->assertSame($text, $news->text);
        $this->assertTrue($news->author->is($author));

        $this->assertSame('news', $jsonResponse['data']['type']);
        $this->assertSame((string) $news->id, $jsonResponse['data']['id']);
        $this->assertSame($news->subject, $jsonResponse['data']['attributes']['subject']);
        $this->assertSame($news->text, $jsonResponse['data']['attributes']['text']);
        $this->assertSame($news->created_at->toAtomString(), $jsonResponse['data']['attributes']['created_at']);
        $this->assertSame($news->updated_at->toAtomString(), $jsonResponse['data']['attributes']['updated_at']);
        $this->assertSame('users', $jsonResponse['data']['relationships']['author']['data']['type']);
        $this->assertSame((string) $news->user_id, $jsonResponse['data']['relationships']['author']['data']['id']);

        $this->assertSame('users', $jsonResponse['included'][0]['type']);
        $this->assertSame((string) $news->user_id, $jsonResponse['included'][0]['id']);
        $this->assertSame($news->author->name, $jsonResponse['included'][0]['attributes']['name']);
    }

    public function testUpdateWithInvalidSubject(): void
    {
        $user = factory(User::class)->create();
        Passport::actingAs($user);

        $news = factory(News::class)->create();

        $this->assertSame(1, News::count());

        $subject = 'n123';

        $data = [
            'data' => [
                'type' => 'news',
                'id' => (string) $news->id,
                'attributes' => [
                    'subject' => $subject,
                ],
            ],
        ];

        $response = $this->makeRequest(
            'PATCH',
            $this->app->make(UrlGenerator::class)->route(
                'admin.news.update',
                ['include' => 'author', 'record' => $news->id]
            ),
            $data
        );

        $response->getResponse()->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertSame(1, News::count());

        $jsonResponse = $response->getJsonResponse();

        $freshNews = $news->fresh();

        $this->assertSame($news->subject, $freshNews->subject);
        $this->assertSame($news->text, $freshNews->text);
        $this->assertTrue($news->author->is($freshNews->author));

        $this->assertSame('422', $jsonResponse['errors'][0]['status']);
        $this->assertSame('Unprocessable Entity', $jsonResponse['errors'][0]['title']);
        $this->assertSame('The subject must be at least 5 characters.', $jsonResponse['errors'][0]['detail']);
        $this->assertSame('/data/attributes/subject', $jsonResponse['errors'][0]['source']['pointer']);
    }

    public function testUpdateWithInvalidText(): void
    {
        $user = factory(User::class)->create();
        Passport::actingAs($user);

        $news = factory(News::class)->create();

        $this->assertSame(1, News::count());

        $text = str_repeat('t', 29);

        $data = [
            'data' => [
                'type' => 'news',
                'id' => (string) $news->id,
                'attributes' => [
                    'text' => $text,
                ],
            ],
        ];

        $response = $this->makeRequest(
            'PATCH',
            $this->app->make(UrlGenerator::class)->route(
                'admin.news.update',
                ['include' => 'author', 'record' => $news->id]
            ),
            $data
        );

        $response->getResponse()->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertSame(1, News::count());

        $jsonResponse = $response->getJsonResponse();

        $freshNews = $news->fresh();

        $this->assertSame($news->subject, $freshNews->subject);
        $this->assertSame($news->text, $freshNews->text);
        $this->assertTrue($news->author->is($freshNews->author));

        $this->assertSame('422', $jsonResponse['errors'][0]['status']);
        $this->assertSame('Unprocessable Entity', $jsonResponse['errors'][0]['title']);
        $this->assertSame('The text must be at least 30 characters.', $jsonResponse['errors'][0]['detail']);
        $this->assertSame('/data/attributes/text', $jsonResponse['errors'][0]['source']['pointer']);
    }
}
