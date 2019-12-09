<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\News;

use App\Models\News;
use App\Models\User;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Passport\Passport;
use Tests\AdminApiTestCase;

final class DeleteEndpointTest extends AdminApiTestCase
{
    use DatabaseTransactions;

    public function testDelete(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        Passport::actingAs($user);

        $news = factory(News::class)->create();

        $this->assertSame(1, News::count());

        $this->makeRequest(
            'DELETE',
            $this->app->make(UrlGenerator::class)->route(
                'admin.news.delete',
                ['record' => $news->id]
            )
        );

        $this->assertSame(0, News::count());
    }
}
