<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Locale;
use Tests\AdminApiTestCase;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LocalesControllerTest extends AdminApiTestCase
{
    use RefreshDatabase;

    public function testIndex()
    {
        $this->withoutExceptionHandling();

        $locale = factory(Locale::class)->create();
        $user = factory(User::class)->create();
        $this->actingAs($user);
        $response = $this->makeRequest('GET', route('admin.locales.index'));
        $jsonResponse = $response->getJsonResponse();

        $this->assertSame(2, $jsonResponse['meta']['total']);
        $this->assertSame($locale->locale, $jsonResponse['data'][0]['attributes']['locale']);
        $this->assertSame($locale->localeShort, $jsonResponse['data'][0]['attributes']['locale-short']);
        $this->assertSame(
            $locale->created_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['created-at']
        );
        $this->assertSame(
            $locale->updated_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['updated-at']
        );
        $this->assertSame(route('admin.locales.read', $locale->id), $jsonResponse['data'][0]['links']['self']);
    }
}
