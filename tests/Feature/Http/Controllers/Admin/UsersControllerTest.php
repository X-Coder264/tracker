<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Http\Models\Torrent;
use App\Http\Models\User;
use App\Http\Models\Locale;
use Tests\AdminApiTestCase;
use Illuminate\Http\Response;
use App\JsonApi\ResourceTypes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UsersControllerTest extends AdminApiTestCase
{
    use RefreshDatabase;

    public function testIndex()
    {
        $this->withoutExceptionHandling();

        $users = factory(User::class, 2)->create();
        $this->actingAs($users[0]);
        $response = $this->makeRequest('GET', route('admin.users.index'));
        $jsonResponse = $response->getJsonResponse();

        $this->assertSame(2, $jsonResponse['meta']['total']);
        $this->assertSame($users[0]->name, $jsonResponse['data'][0]['attributes']['name']);
        $this->assertSame($users[0]->email, $jsonResponse['data'][0]['attributes']['email']);
        $this->assertSame($users[0]->slug, $jsonResponse['data'][0]['attributes']['slug']);
        $this->assertSame($users[0]->timezone, $jsonResponse['data'][0]['attributes']['timezone']);
        $this->assertSame(
            $users[0]->created_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['created-at']
        );
        $this->assertSame(
            $users[0]->updated_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['updated-at']
        );
        $this->assertSame(route('admin.users.read', $users[0]->id), $jsonResponse['data'][0]['links']['self']);
        $this->assertSame($users[1]->name, $jsonResponse['data'][1]['attributes']['name']);
        $this->assertSame($users[1]->email, $jsonResponse['data'][1]['attributes']['email']);
        $this->assertSame($users[1]->slug, $jsonResponse['data'][1]['attributes']['slug']);
        $this->assertSame($users[1]->timezone, $jsonResponse['data'][1]['attributes']['timezone']);
        $this->assertSame(
            $users[1]->created_at->format(Carbon::W3C),
            $jsonResponse['data'][1]['attributes']['created-at']
        );
        $this->assertSame(
            $users[1]->updated_at->format(Carbon::W3C),
            $jsonResponse['data'][1]['attributes']['updated-at']
        );
        $this->assertSame(route('admin.users.read', $users[1]->id), $jsonResponse['data'][1]['links']['self']);
    }

    public function testCreate()
    {
        $this->withoutExceptionHandling();

        $users = factory(User::class, 2)->create();
        $locale = factory(Locale::class)->create();
        $this->actingAs($users[0]);

        $email = 'test@gmail.com';
        $name = 'test name';
        $password = 'password';
        $timezone = 'Europe/Zagreb';

        $data = [
            'data' => [
                'type' => ResourceTypes::USER,
                'attributes' => [
                    'email' => $email,
                    'name' => $name,
                    'password' => $password,
                    'timezone' => $timezone,
                ],
                'relationships' => [
                    'locale' => [
                        'data' => [
                            'type' => ResourceTypes::LOCALE, 'id' => (string) $locale->id,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->makeRequest('POST', route('admin.users.create'), $data);
        $jsonResponse = $response->getJsonResponse();

        $this->assertSame(3, (int) $jsonResponse['data']['id']);
        $this->assertSame($name, $jsonResponse['data']['attributes']['name']);
        $this->assertSame($email, $jsonResponse['data']['attributes']['email']);
        $this->assertSame($timezone, $jsonResponse['data']['attributes']['timezone']);
        $this->assertSame(route('admin.users.read', 3), $jsonResponse['data']['links']['self']);
        $this->assertArrayHasKey('slug', $jsonResponse['data']['attributes']);
        $this->assertArrayHasKey('created-at', $jsonResponse['data']['attributes']);
        $this->assertArrayHasKey('updated-at', $jsonResponse['data']['attributes']);
        $this->assertArrayNotHasKey('password', $jsonResponse['data']['attributes']);
        $this->assertSame(3, User::count());
        $user = User::findOrFail(3);
        $this->assertSame($name, $user->name);
        $this->assertSame($email, $user->email);
        $this->assertSame($timezone, $user->timezone);
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertNull($user->passkey);
        $this->assertNotNull($user->slug);
        $this->assertTrue($user->language->is($locale));
    }

    public function testInvalidCreate()
    {
        $users = factory(User::class, 2)->create();
        $locale = factory(Locale::class)->create();
        $this->actingAs($users[0]);

        $email = 'test@gmail.com';
        $name = 'test name';
        $password = '1234567';
        $timezone = 'Europe/Zagreb';

        $data = [
            'data' => [
                'type' => ResourceTypes::USER,
                'attributes' => [
                    'email' => $email,
                    'name' => $name,
                    'password' => $password,
                    'timezone' => $timezone,
                ],
                'relationships' => [
                    'locale' => [
                        'data' => [
                            'type' => ResourceTypes::LOCALE, 'id' => (string) $locale->id,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->makeRequest('POST', route('admin.users.create'), $data);
        $jsonResponse = $response->getJsonResponse();

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, (int) $jsonResponse['errors'][0]['status']);
        $this->assertArrayHasKey('title', $jsonResponse['errors'][0]);
        $this->assertArrayHasKey('detail', $jsonResponse['errors'][0]);
        $this->assertArrayHasKey('source', $jsonResponse['errors'][0]);
    }

    public function testRead()
    {
        $this->withoutExceptionHandling();

        $locale = factory(Locale::class)->create();
        $user = factory(User::class)->create(['locale_id' => $locale->id]);
        $torrent = factory(Torrent::class)->create(['uploader_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->makeRequest('GET', route('admin.users.read', $user->id));
        $jsonResponse = $response->getJsonResponse();

        $this->assertSame($user->id, (int) $jsonResponse['data']['id']);
        $this->assertSame($user->name, $jsonResponse['data']['attributes']['name']);
        $this->assertSame($user->email, $jsonResponse['data']['attributes']['email']);
        $this->assertSame(route('admin.users.read', $user->id), $jsonResponse['data']['links']['self']);
        $this->assertSame($user->timezone, $jsonResponse['data']['attributes']['timezone']);
        $this->assertSame($user->slug, $jsonResponse['data']['attributes']['slug']);
        $this->assertSame($user->created_at->format(Carbon::W3C), $jsonResponse['data']['attributes']['created-at']);
        $this->assertArrayNotHasKey('password', $jsonResponse['data']['attributes']);
        $this->assertSame(ResourceTypes::TORRENT, $jsonResponse['included'][0]['type']);
        $this->assertSame($torrent->id, (int) $jsonResponse['included'][0]['id']);
        $this->assertNotEmpty($jsonResponse['included'][0]['attributes']);
        $this->assertSame(ResourceTypes::LOCALE, $jsonResponse['included'][1]['type']);
        $this->assertSame($locale->id, (int) $jsonResponse['included'][1]['id']);
        $this->assertNotEmpty($jsonResponse['included'][1]['attributes']);
    }

    public function testUpdate()
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        $locale = factory(Locale::class)->create();
        $this->actingAs($user);

        $name = 'test name 2';

        $data = [
            'data' => [
                'type' => ResourceTypes::USER,
                'id' => (string) $user->id,
                'attributes' => [
                    'name' => $name,
                ],
                'relationships' => [
                    'locale' => [
                        'data' => [
                            'type' => ResourceTypes::LOCALE, 'id' => (string) $locale->id,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->makeRequest('PATCH', route('admin.users.update', $user->id), $data);
        $jsonResponse = $response->getJsonResponse();

        $this->assertSame($user->id, (int) $jsonResponse['data']['id']);
        $this->assertSame($name, $jsonResponse['data']['attributes']['name']);
        $this->assertSame($user->email, $jsonResponse['data']['attributes']['email']);
        $this->assertSame(route('admin.users.read', $user->id), $jsonResponse['data']['links']['self']);
        $this->assertSame($user->slug, $jsonResponse['data']['attributes']['slug']);
        $this->assertSame($user->created_at->format(Carbon::W3C), $jsonResponse['data']['attributes']['created-at']);
        $this->assertArrayNotHasKey('password', $jsonResponse['data']['attributes']);
        $this->assertSame(1, User::count());
        $updatedUser = User::findOrFail($user->id);
        $this->assertSame(
            $updatedUser->updated_at->format(Carbon::W3C),
            $jsonResponse['data']['attributes']['updated-at']
        );
        $this->assertSame($name, $updatedUser->name);
        $this->assertSame($user->email, $updatedUser->email);
        $this->assertSame($user->timezone, $updatedUser->timezone);
        $this->assertSame($user->password, $updatedUser->password);
        $this->assertSame($user->slug, $updatedUser->slug);
        $this->assertSame($user->passkey, $updatedUser->passkey);
        $this->assertTrue($updatedUser->language->is($locale));
    }

    public function testNameFilter()
    {
        $this->withoutExceptionHandling();

        $userJohn = factory(User::class)->create(['name' => 'John']);
        $userDoe = factory(User::class)->create(['name' => 'Doe']);
        $this->actingAs($userJohn);
        $response = $this->makeRequest('GET', route('admin.users.index', ['filter[name]' => 'Doe']));
        $jsonResponse = $response->getJsonResponse();

        $this->assertSame(1, $jsonResponse['meta']['total']);
        $this->assertSame($userDoe->name, $jsonResponse['data'][0]['attributes']['name']);
        $this->assertSame($userDoe->email, $jsonResponse['data'][0]['attributes']['email']);
        $this->assertSame($userDoe->slug, $jsonResponse['data'][0]['attributes']['slug']);
        $this->assertSame(
            $userDoe->created_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['created-at']
        );
        $this->assertSame(
            $userDoe->updated_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['updated-at']
        );
        $this->assertSame(route('admin.users.read', $userDoe->id), $jsonResponse['data'][0]['links']['self']);
        $this->assertCount(1, $jsonResponse['data']);
    }

    public function testSlugFilter()
    {
        $this->withoutExceptionHandling();

        $userJohn = factory(User::class)->create(['slug' => 'john']);
        $userDoe = factory(User::class)->create(['slug' => 'doe']);
        $this->actingAs($userJohn);
        $response = $this->makeRequest('GET', route('admin.users.index', ['filter[slug]' => 'doe']));
        $jsonResponse = $response->getJsonResponse();

        $this->assertSame(1, $jsonResponse['meta']['total']);
        $this->assertSame($userDoe->name, $jsonResponse['data'][0]['attributes']['name']);
        $this->assertSame($userDoe->email, $jsonResponse['data'][0]['attributes']['email']);
        $this->assertSame($userDoe->slug, $jsonResponse['data'][0]['attributes']['slug']);
        $this->assertSame(
            $userDoe->created_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['created-at']
        );
        $this->assertSame(
            $userDoe->updated_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['updated-at']
        );
        $this->assertSame(route('admin.users.read', $userDoe->id), $jsonResponse['data'][0]['links']['self']);
        $this->assertCount(1, $jsonResponse['data']);
    }

    public function testIdFilter()
    {
        $this->withoutExceptionHandling();

        $userJohn = factory(User::class)->create(['name' => 'john']);
        $userDoe = factory(User::class)->create(['name' => 'doe']);
        $this->actingAs($userJohn);
        $response = $this->makeRequest('GET', route('admin.users.index', ['filter[id]' => $userDoe->id]));
        $jsonResponse = $response->getJsonResponse();

        $this->assertCount(1, $jsonResponse['data']);
        $this->assertSame($userDoe->name, $jsonResponse['data'][0]['attributes']['name']);
        $this->assertSame($userDoe->email, $jsonResponse['data'][0]['attributes']['email']);
        $this->assertSame($userDoe->slug, $jsonResponse['data'][0]['attributes']['slug']);
        $this->assertSame(
            $userDoe->created_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['created-at']
        );
        $this->assertSame(
            $userDoe->updated_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['updated-at']
        );
        $this->assertSame(route('admin.users.read', $userDoe->id), $jsonResponse['data'][0]['links']['self']);
        $this->assertCount(1, $jsonResponse['data']);
    }

    public function testEmailFilter()
    {
        $this->withoutExceptionHandling();

        $userJohn = factory(User::class)->create(['email' => 'test@gmail.com']);
        $userDoe = factory(User::class)->create(['email' => 'test@example.com']);
        $this->actingAs($userJohn);
        $response = $this->makeRequest('GET', route('admin.users.index', ['filter[email]' => $userDoe->email]));
        $jsonResponse = $response->getJsonResponse();

        $this->assertSame(1, $jsonResponse['meta']['total']);
        $this->assertSame($userDoe->name, $jsonResponse['data'][0]['attributes']['name']);
        $this->assertSame($userDoe->email, $jsonResponse['data'][0]['attributes']['email']);
        $this->assertSame($userDoe->slug, $jsonResponse['data'][0]['attributes']['slug']);
        $this->assertSame(
            $userDoe->created_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['created-at']
        );
        $this->assertSame(
            $userDoe->updated_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['updated-at']
        );
        $this->assertSame(route('admin.users.read', $userDoe->id), $jsonResponse['data'][0]['links']['self']);
        $this->assertCount(1, $jsonResponse['data']);
    }

    public function testTimezoneFilter()
    {
        $this->withoutExceptionHandling();

        $userJohn = factory(User::class)->create(['timezone' => 'Europe/Zagreb']);
        $userDoe = factory(User::class)->create(['timezone' => 'Europe/Paris']);
        $this->actingAs($userJohn);
        $response = $this->makeRequest('GET', route('admin.users.index', ['filter[timezone]' => $userDoe->timezone]));
        $jsonResponse = $response->getJsonResponse();

        $this->assertSame(1, $jsonResponse['meta']['total']);
        $this->assertSame($userDoe->name, $jsonResponse['data'][0]['attributes']['name']);
        $this->assertSame($userDoe->email, $jsonResponse['data'][0]['attributes']['email']);
        $this->assertSame($userDoe->slug, $jsonResponse['data'][0]['attributes']['slug']);
        $this->assertSame(
            $userDoe->created_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['created-at']
        );
        $this->assertSame(
            $userDoe->updated_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['updated-at']
        );
        $this->assertSame(route('admin.users.read', $userDoe->id), $jsonResponse['data'][0]['links']['self']);
        $this->assertCount(1, $jsonResponse['data']);
    }

    public function testPagination()
    {
        $this->withoutExceptionHandling();

        $users = factory(User::class, 3)->create();
        $this->actingAs($users[0]);
        $response = $this->makeRequest('GET', route('admin.users.index', ['page[offset]' => 0, 'page[limit]' => 2, 'sort' => 'id']));
        $jsonResponse = $response->getJsonResponse();

        $this->assertSame(3, $jsonResponse['meta']['total']);
        $this->assertSame(1, (int) $jsonResponse['data'][0]['id']);
        $this->assertSame(2, (int) $jsonResponse['data'][1]['id']);
        $this->assertCount(2, $jsonResponse['data']);

        $response = $this->makeRequest('GET', route('admin.users.index', ['page[offset]' => 1, 'page[limit]' => 2, 'sort' => 'id']));
        $jsonResponse = $response->getJsonResponse();

        $this->assertSame(3, $jsonResponse['meta']['total']);
        $this->assertSame(3, (int) $jsonResponse['data'][0]['id']);
        $this->assertCount(1, $jsonResponse['data']);

        $response = $this->makeRequest('GET', route('admin.users.index', ['page[offset]' => 2, 'page[limit]' => 2, 'sort' => 'id']));
        $jsonResponse = $response->getJsonResponse();

        $this->assertSame(3, $jsonResponse['meta']['total']);
        $this->assertSame([], $jsonResponse['data']);
    }

    public function testTorrentsInclude()
    {
        $this->withoutExceptionHandling();

        $locale = factory(Locale::class)->create();
        $user = factory(User::class)->create(['locale_id' => $locale->id]);
        $torrent = factory(Torrent::class)->create(['uploader_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->makeRequest('GET', route('admin.users.read', [$user->id, 'include' => 'torrents']));
        $jsonResponse = $response->getJsonResponse();

        $this->assertSame($user->id, (int) $jsonResponse['data']['id']);
        $this->assertSame($user->name, $jsonResponse['data']['attributes']['name']);
        $this->assertSame($user->email, $jsonResponse['data']['attributes']['email']);
        $this->assertSame(route('admin.users.read', $user->id), $jsonResponse['data']['links']['self']);
        $this->assertSame($user->timezone, $jsonResponse['data']['attributes']['timezone']);
        $this->assertSame($user->slug, $jsonResponse['data']['attributes']['slug']);
        $this->assertSame($user->created_at->format(Carbon::W3C), $jsonResponse['data']['attributes']['created-at']);
        $this->assertArrayNotHasKey('password', $jsonResponse['data']['attributes']);
        $this->assertSame(ResourceTypes::TORRENT, $jsonResponse['included'][0]['type']);
        $this->assertSame($torrent->id, (int) $jsonResponse['included'][0]['id']);
        $this->assertNotEmpty($jsonResponse['included'][0]['attributes']);
        $this->assertArrayNotHasKey(1, $jsonResponse['included']);
    }

    public function testLocaleInclude()
    {
        $this->withoutExceptionHandling();

        $locale = factory(Locale::class)->create();
        $user = factory(User::class)->create(['locale_id' => $locale->id]);
        $torrent = factory(Torrent::class)->create(['uploader_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->makeRequest('GET', route('admin.users.read', [$user->id, 'include' => 'locale']));
        $jsonResponse = $response->getJsonResponse();

        $this->assertSame($user->id, (int) $jsonResponse['data']['id']);
        $this->assertSame($user->name, $jsonResponse['data']['attributes']['name']);
        $this->assertSame($user->email, $jsonResponse['data']['attributes']['email']);
        $this->assertSame(route('admin.users.read', $user->id), $jsonResponse['data']['links']['self']);
        $this->assertSame($user->timezone, $jsonResponse['data']['attributes']['timezone']);
        $this->assertSame($user->slug, $jsonResponse['data']['attributes']['slug']);
        $this->assertSame($user->created_at->format(Carbon::W3C), $jsonResponse['data']['attributes']['created-at']);
        $this->assertArrayNotHasKey('password', $jsonResponse['data']['attributes']);
        $this->assertSame(ResourceTypes::LOCALE, $jsonResponse['included'][0]['type']);
        $this->assertSame($locale->id, (int) $jsonResponse['included'][0]['id']);
        $this->assertNotEmpty($jsonResponse['included'][0]['attributes']);
        $this->assertArrayNotHasKey(1, $jsonResponse['included']);
    }
}
