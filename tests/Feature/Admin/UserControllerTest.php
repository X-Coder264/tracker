<?php

namespace Tests\Feature\Admin;

use App\Http\Models\User;
use Tests\AdminApiTestCase;
use App\Http\Models\Locale;
use App\JsonApi\ResourceTypes;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserControllerTest extends AdminApiTestCase
{
    use RefreshDatabase;

    public function testIndex()
    {
        $users = factory(User::class, 2)->create();
        $this->actingAs($users[0]);
        $response = $this->makeRequest('GET', route('admin.user.index'));
        $jsonResponse = $response->getJsonResponse();

        $this->assertSame(2, $jsonResponse['meta']['total']);
        $this->assertSame($users[0]->name, $jsonResponse['data'][0]['attributes']['name']);
        $this->assertSame($users[0]->email, $jsonResponse['data'][0]['attributes']['email']);
        $this->assertSame($users[0]->slug, $jsonResponse['data'][0]['attributes']['slug']);
        $this->assertSame(route('admin.user.read', $users[0]->id), $jsonResponse['data'][0]['links']['self']);
        $this->assertSame($users[1]->name, $jsonResponse['data'][1]['attributes']['name']);
        $this->assertSame($users[1]->email, $jsonResponse['data'][1]['attributes']['email']);
        $this->assertSame($users[1]->slug, $jsonResponse['data'][1]['attributes']['slug']);
        $this->assertSame(route('admin.user.read', $users[1]->id), $jsonResponse['data'][1]['links']['self']);
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
                    'timezone' => $timezone
                ],
                'relationships' => [
                    'locale' => [
                        'data' => [
                            'type' => ResourceTypes::LOCALE, 'id' => (string) $locale->id,
                        ],
                    ],
                ]
            ]
        ];

        $response = $this->makeRequest('POST', route('admin.user.create'), $data);
        $jsonResponse = $response->getJsonResponse();

        $this->assertSame(3, (int) $jsonResponse['data']['id']);
        $this->assertSame($name, $jsonResponse['data']['attributes']['name']);
        $this->assertSame($email, $jsonResponse['data']['attributes']['email']);
        $this->assertSame(route('admin.user.read', 3), $jsonResponse['data']['links']['self']);
        $this->assertArrayHasKey('slug', $jsonResponse['data']['attributes']);
        $this->assertArrayHasKey('created-at', $jsonResponse['data']['attributes']);
        $this->assertArrayHasKey('updated-at', $jsonResponse['data']['attributes']);
        $this->assertArrayNotHasKey('password', $jsonResponse['data']['attributes']);
        $this->assertSame(3, User::count());
        $user = User::findOrFail(3);
        $this->assertSame($name, $user->name);
        $this->assertSame($email, $user->email);
        $this->assertSame($timezone, $user->timezone);
        $this->assertSame(60, strlen($user->password));
        $this->assertNull($user->passkey);
        $this->assertNotNull($user->slug);
        $this->assertTrue($user->language->is($locale));
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
                ]
            ]
        ];

        $response = $this->makeRequest('PATCH', route('admin.user.update', $user->id), $data);
        $jsonResponse = $response->getJsonResponse();

        $this->assertSame($user->id, (int) $jsonResponse['data']['id']);
        $this->assertSame($name, $jsonResponse['data']['attributes']['name']);
        $this->assertSame($user->email, $jsonResponse['data']['attributes']['email']);
        $this->assertSame(route('admin.user.read', $user->id), $jsonResponse['data']['links']['self']);
        $this->assertSame($user->slug, $jsonResponse['data']['attributes']['slug']);
        $this->assertArrayHasKey('created-at', $jsonResponse['data']['attributes']);
        $this->assertArrayHasKey('updated-at', $jsonResponse['data']['attributes']);
        $this->assertArrayNotHasKey('password', $jsonResponse['data']['attributes']);
        $this->assertSame(1, User::count());
        $updatedUser = User::findOrFail($user->id);
        $this->assertSame($name, $updatedUser->name);
        $this->assertSame($user->email, $updatedUser->email);
        $this->assertSame($user->timezone, $updatedUser->timezone);
        $this->assertSame($user->password, $updatedUser->password);
        $this->assertSame($user->slug, $updatedUser->slug);
        $this->assertSame($user->passkey, $updatedUser->passkey);
        $this->assertTrue($updatedUser->language->is($locale));
    }
}
