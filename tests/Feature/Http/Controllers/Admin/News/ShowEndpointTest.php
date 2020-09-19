<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\News;

use App\Models\News;
use Database\Factories\NewsFactory;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Passport\Passport;
use Tests\AdminApiTestCase;

final class ShowEndpointTest extends AdminApiTestCase
{
    use DatabaseTransactions;

    public function testShowWithAuthorInclude(): void
    {
        $this->withoutExceptionHandling();

        $user = UserFactory::new()->create();

        Passport::actingAs($user);

        /** @var News $news */
        $news = NewsFactory::new()->create();

        $response = $this->makeRequest(
            'GET',
            $this->app->make(UrlGenerator::class)->route(
                'admin.news.read',
                ['include' => 'author', 'record' => $news->id]
            )
        );

        $response->getResponse()->assertOk();

        $jsonResponse = $response->getJsonResponse();

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
}
