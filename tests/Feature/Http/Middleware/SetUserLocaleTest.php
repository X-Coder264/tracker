<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\Locale;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SetUserLocaleTest extends TestCase
{
    use DatabaseTransactions;

    public function testSettingUserLocale()
    {
        $locale = factory(Locale::class)->create(['localeShort' => 'hr']);
        $user = factory(User::class)->create(['locale_id' => $locale->id]);
        $this->actingAs($user);

        Route::middleware('web')->group(function () {
            Route::get('test', function () {
                return response()->json([
                    'hello' => 'world',
                ]);
            });
        });

        $response = $this->get('test');
        $response->assertStatus(200);
        $this->assertSame(['hello' => 'world'], json_decode($response->getContent(), true));
        $this->assertSame('hr', $this->app->getLocale());
        $this->assertSame('hr', Carbon::getLocale());
        $this->assertSame('hr', $this->app->make(Repository::class)->get('user.' . $user->slug . '.locale'));
    }
}
