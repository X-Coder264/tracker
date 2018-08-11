<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\Locale;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
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
     * @param ValidatorFactory $validatorFactory
     * @param UrlGenerator     $urlGenerator
     */
    public function __construct(ValidatorFactory $validatorFactory, UrlGenerator $urlGenerator)
    {
        $this->middleware('guest');
        $this->validatorFactory = $validatorFactory;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Show the application registration form.
     *
     * @param ResponseFactory $responseFactory
     *
     * @return Response
     */
    public function showRegistrationForm(ResponseFactory $responseFactory): Response
    {
        $locales = Locale::all();

        return $responseFactory->view('auth.register', compact('locales'));
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     *
     * @return Validator
     */
    protected function validator(array $data): Validator
    {
        $locales = Locale::select('id')->get();
        $localeIDs = $locales->pluck('id')->toArray();

        return $this->validatorFactory->make($data, [
            'name'     => 'required|string|max:255|unique:users',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'locale'   => [
                'required',
                Rule::in($localeIDs),
            ],
            'timezone' => 'required|timezone',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     *
     * @return User
     */
    protected function create(array $data): User
    {
        return User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => $data['password'],
            'timezone'  => $data['timezone'],
            'locale_id' => $data['locale'],
        ]);
    }

    /**
     * @return string
     */
    public function redirectTo(): string
    {
        return $this->urlGenerator->route('home');
    }
}
