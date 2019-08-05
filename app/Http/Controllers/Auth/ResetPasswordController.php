<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Foundation\Auth\ResetsPasswords;
use App\Http\Middleware\RedirectIfAuthenticated;

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

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var Hasher
     */
    private $hasher;

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
