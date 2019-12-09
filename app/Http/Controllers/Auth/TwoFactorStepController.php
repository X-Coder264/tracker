<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;
use Symfony\Component\HttpFoundation\Response;

final class TwoFactorStepController
{
    use AuthenticatesUsers;

    /**
     * @var StatefulGuard
     */
    private $guard;

    /**
     * @var Factory
     */
    private $validatorFactory;

    /**
     * @var Google2FA
     */
    private $google2FA;

    /**
     * @var Encrypter
     */
    private $encrypter;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(
        Guard $guard,
        Factory $validatorFactory,
        Google2FA $google2FA,
        Encrypter $encrypter,
        UrlGenerator $urlGenerator,
        ResponseFactory $responseFactory
    ) {
        $this->guard = $guard;
        $this->validatorFactory = $validatorFactory;
        $this->google2FA = $google2FA;
        $this->encrypter = $encrypter;
        $this->urlGenerator = $urlGenerator;
        $this->responseFactory = $responseFactory;
    }

    public function showTwoFactorStep(): Response
    {
        if ($this->guard->check()) {
            return $this->responseFactory->redirectToRoute('home');
        }

        return $this->responseFactory->view('auth.2fa_login_step');
    }

    public function verifyTwoFactorStep(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('2fa_user_id');

        if (empty($userId)) {
            return $this->responseFactory->redirectToRoute('login');
        }

        $userId = $this->encrypter->decrypt($userId);
        $rememberMe = $request->session()->get('2fa_remember_me', false);

        /** @var User|null $user */
        $user = User::find($userId);

        if (null === $user) {
            $this->forget2FASessionParameters($request);

            return $this->responseFactory->redirectToRoute('login');
        }

        $this->validatorFactory->extend(
            'validTwoFactorCode',
            function (string $attribute, string $value, array $parameters, Validator $validator): bool {
                return $this->google2FA->verifyKey($parameters[0], $value);
            }
        );

        $validator = $this->validatorFactory->make(
            $request->all(),
            [
                'code' => "required|string|validTwoFactorCode:{$user->two_factor_secret_key}",
            ]
        );

        if ($validator->fails()) {
            return $this->responseFactory->redirectToRoute('2fa.show_form')
                ->withInput()
                ->withErrors($validator);
        }

        $this->forget2FASessionParameters($request);

        $this->guard->loginUsingId($userId, $rememberMe);

        return $this->sendLoginResponse($request);
    }

    private function redirectTo(): string
    {
        return $this->urlGenerator->route('home');
    }

    private function forget2FASessionParameters(Request $request): void
    {
        $request->session()->forget('2fa_user_id');
        $request->session()->forget('2fa_remember_me');
    }
}
