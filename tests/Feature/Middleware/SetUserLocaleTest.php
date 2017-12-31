<?php

namespace Tests\Feature\Middleware;

use Carbon\Carbon;
use Tests\TestCase;
use App\Http\Models\User;
use App\Http\Models\Locale;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SetUserLocaleTest extends TestCase
{
    use RefreshDatabase;

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

        $this->get('test');
        $this->assertSame('hr', $this->app->getLocale());
        $this->assertSame('hr', Carbon::getLocale());
        $this->assertSame('hr', Cache::get('user.' . $user->slug . '.locale'));
    }
}
