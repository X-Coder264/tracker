<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Middleware\RedirectIfAuthenticated;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/';

    private Dispatcher $dispatcher;
    private Hasher $hasher;

    public function __construct(Dispatcher $dispatcher, Hasher $hasher)
    {
        $this->middleware(RedirectIfAuthenticated::class);
        $this->dispatcher = $dispatcher;
        $this->hasher = $hasher;
    }

    protected function rules(): array
    {
        return [
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|confirmed|min:8',
        ];
    }

    /**
     * Reset the given user's password.
     *
     * @param CanResetPassword|Authenticatable|User $user
     * @param string                                $password
     */
    protected function resetPassword($user, $password)
    {
        $user->password = $this->hasher->make($password);

        $user->setRememberToken(Str::random(60));

        $user->save();

        $this->dispatcher->dispatch(new PasswordReset($user));

        $this->guard()->login($user);
    }
}
