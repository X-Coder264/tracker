<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\Invite;
use App\Models\Locale;
use App\Models\Configuration;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Routing\Controller;
use Illuminate\Contracts\Hashing\Hasher;
use App\Enumerations\ConfigurationOptions;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * @var ValidatorFactory
     */
    private $validatorFactory;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @var Hasher
     */
    private $hasher;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(
        ValidatorFactory $validatorFactory,
        UrlGenerator $urlGenerator,
        Hasher $hasher,
        ResponseFactory $responseFactory
    ) {
        $this->middleware(RedirectIfAuthenticated::class);
        $this->validatorFactory = $validatorFactory;
        $this->urlGenerator = $urlGenerator;
        $this->hasher = $hasher;
        $this->responseFactory = $responseFactory;
    }

    public function showRegistrationForm(): Response
    {
        $locales = Locale::all();

        $isRegistrationInviteOnly = (bool) Configuration::getConfigurationValue(ConfigurationOptions::INVITE_ONLY_SIGNUP)->firstOrFail()->value;

        return $this->responseFactory->view('auth.register', compact('locales', 'isRegistrationInviteOnly'));
    }

    protected function validator(array $data): Validator
    {
        $locales = Locale::select('id')->get();
        $localeIDs = $locales->pluck('id')->toArray();

        $isRegistrationInviteOnly = (bool) Configuration::getConfigurationValue(ConfigurationOptions::INVITE_ONLY_SIGNUP)->firstOrFail()->value;

        $rules = [
            'name'     => 'required|string|max:255|unique:users',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'locale'   => [
                'required',
                Rule::in($localeIDs),
            ],
            'timezone' => 'required|timezone',
        ];

        if ($isRegistrationInviteOnly) {
            $rules['invite'] = 'required|string|max:255|exists:invites,code';
        }

        return $this->validatorFactory->make($data, $rules);
    }

    protected function create(array $data): User
    {
        $inviterId = null;
        $invite = null;
        if (! empty($data['invite'])) {
            $invite = Invite::where('code', '=', $data['invite'])->firstOrFail();
            $inviterId = $invite->user_id;
        }

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => $this->hasher->make($data['password']),
            'timezone'  => $data['timezone'],
            'locale_id' => $data['locale'],
            'inviter_user_id' => $inviterId,
        ]);

        if ($invite instanceof Invite) {
            $invite->delete();
        }

        return $user;
    }

    public function redirectTo(): string
    {
        return $this->urlGenerator->route('home');
    }
}
