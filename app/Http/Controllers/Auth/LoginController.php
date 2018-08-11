<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Redirector;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @param UrlGenerator $urlGenerator
     */
    public function __construct(UrlGenerator $urlGenerator)
    {
        $this->middleware('guest')->except('logout');
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @return string
     */
    public function redirectTo(): string
    {
        return $this->urlGenerator->route('home');
    }

    /**
     * Handle a login request to the application.
     *
     * @param Request     $request
     * @param AuthManager $authManager
     * @param Redirector  $redirector
     * @param Translator  $translator
     *
     * @throws ValidationException
     *
     * @return RedirectResponse|Response|JsonResponse
     */
    public function login(Request $request, AuthManager $authManager, Redirector $redirector, Translator $translator): BaseResponse
    {
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            if (true === $authManager->guard()->user()->banned) {
                $authManager->guard()->logout();

                return $redirector->route('login')->withInput()->with('error', $translator->trans('messages.user.banned'));
            }

            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }
}
