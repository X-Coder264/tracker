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

final class CreateEndpointTest extends AdminApiTestCase
{
    use DatabaseTransactions;

    public function testCreateWithAuthorInclude(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();

        Passport::actingAs($user);

        $this->assertSame(0, News::count());

        $subject = 'News 123';
        $text = str_repeat('Important news!!!', 5);

        $data = [
            'data' => [
                'type' => 'news',
                'attributes' => [
                    'subject' => $subject,
                    'text' => $text,
                ],
            ],
        ];

        $response = $this->makeRequest(
            'POST',
            $this->app->make(UrlGenerator::class)->route('admin.news.create', ['include' => 'author']),
            $data
        );

        $response->getResponse()->assertStatus(Response::HTTP_CREATED);

        $this->assertSame(1, News::count());

        $jsonResponse = $response->getJsonResponse();

        $news = News::firstOrFail();

        $this->assertSame($subject, $news->subject);
        $this->assertSame($text, $news->text);
        $this->assertTrue($news->author->is($user));
        $this->assertNotEmpty($news->slug);

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

    public function testCreateWithInvalidSubject(): void
    {
        $user = factory(User::class)->create();

        Passport::actingAs($user);

        $this->assertSame(0, News::count());

        $subject = 'n123';
        $text = str_repeat('Important news!!!', 5);

        $data = [
            'data' => [
                'type' => 'news',
                'attributes' => [
                    'subject' => $subject,
                    'text' => $text,
                ],
            ],
        ];

        $response = $this->makeRequest(
            'POST',
            $this->app->make(UrlGenerator::class)->route('admin.news.create', ['include' => 'author']),
            $data
        );

        $response->getResponse()->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertSame(0, News::count());

        $jsonResponse = $response->getJsonResponse();

        $this->assertSame('422', $jsonResponse['errors'][0]['status']);
        $this->assertSame('Unprocessable Entity', $jsonResponse['errors'][0]['title']);
        $this->assertSame('The subject must be at least 5 characters.', $jsonResponse['errors'][0]['detail']);
        $this->assertSame('/data/attributes/subject', $jsonResponse['errors'][0]['source']['pointer']);
    }

    public function testCreateWithInvalidText(): void
    {
        $user = factory(User::class)->create();

        Passport::actingAs($user);

        $this->assertSame(0, News::count());

        $subject = 'News 123';
        $text = str_repeat('t', 29);

        $data = [
            'data' => [
                'type' => 'news',
                'attributes' => [
                    'subject' => $subject,
                    'text' => $text,
                ],
            ],
        ];

        $response = $this->makeRequest(
            'POST',
            $this->app->make(UrlGenerator::class)->route('admin.news.create', ['include' => 'author']),
            $data
        );

        $response->getResponse()->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertSame(0, News::count());

        $jsonResponse = $response->getJsonResponse();

        $this->assertSame('422', $jsonResponse['errors'][0]['status']);
        $this->assertSame('Unprocessable Entity', $jsonResponse['errors'][0]['title']);
        $this->assertSame('The text must be at least 30 characters.', $jsonResponse['errors'][0]['detail']);
        $this->assertSame('/data/attributes/text', $jsonResponse['errors'][0]['source']['pointer']);
    }
}
