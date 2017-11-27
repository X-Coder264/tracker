<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use DateTimeZone;
use App\Http\Models\User;
use App\Http\Models\Locale;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

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
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Show the application registration form.
     *
     * @return Response
     */
    public function showRegistrationForm(): Response
    {
        $locales = Locale::all();

        return response()->view('auth.register', compact('locales'));
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     *
     * @return ValidatorContract
     */
    protected function validator(array $data): ValidatorContract
    {
        $locales = Locale::select('id')->get();
        $localeIDs = $locales->pluck('id')->toArray();
        $timezoneIdentifiers = DateTimeZone::listIdentifiers();

        return Validator::make($data, [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'locale' => [
                'required',
                Rule::in($localeIDs),
            ],
            'timezone' => [
                'required',
                Rule::in($timezoneIdentifiers),
            ],
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
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password'], ['rounds' => 15]),
            'timezone' => $data['timezone'],
            'locale_id' => $data['locale'],
        ]);
    }
}
