<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Validation\ValidationException;
use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Routing\ResponseFactory;
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
     * @var AuthManager
     */
    private $authManager;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var Encrypter
     */
    private $encrypter;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(
        UrlGenerator $urlGenerator,
        AuthManager $authManager,
        Translator $translator,
        Encrypter $encrypter,
        ResponseFactory $responseFactory
    ) {
        $this->middleware(RedirectIfAuthenticated::class)->except('logout');

        $this->urlGenerator = $urlGenerator;
        $this->authManager = $authManager;
        $this->translator = $translator;
        $this->encrypter = $encrypter;
        $this->responseFactory = $responseFactory;
    }

    public function redirectTo(): string
    {
        return $this->urlGenerator->route('home');
    }

    /**
     * Handle a login request to the application.
     *
     *
     * @throws ValidationException
     *
     * @return RedirectResponse|Response|JsonResponse
     */
    public function login(Request $request): BaseResponse
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
            /** @var User $user */
            $user = $this->authManager->guard()->user();

            if (true === $user->banned) {
                $this->authManager->guard()->logout();

                return $this->responseFactory->redirectToRoute('login')
                    ->withInput()
                    ->with('error', $this->translator->get('messages.user.banned'));
            }

            $response = $this->sendLoginResponse($request);

            if (! $user->is_two_factor_enabled) {
                return $response;
            }

            $this->authManager->guard()->logout();
            $request->session()->put('2fa_user_id', $this->encrypter->encrypt($user->id));
            $request->session()->put('2fa_remember_me', $request->filled('remember'));

            return $this->responseFactory->redirectToRoute('2fa.show_form');
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }
}
