<?php

declare(strict_types=1);

namespace App\Http\Controllers\TwoFactorAuth;

use App\Models\User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use PragmaRX\Google2FAQRCode\Google2FA;

final class StatusController
{
    private Google2FA $google2FA;
    private Guard $guard;
    private Repository $config;
    private ResponseFactory $responseFactory;

    public function __construct(Google2FA $google2FA, Guard $guard, Repository $config, ResponseFactory $responseFactory)
    {
        $this->google2FA = $google2FA;
        $this->guard = $guard;
        $this->config = $config;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(): Response
    {
        /** @var User $user */
        $user = $this->guard->user();

        $barcode = null;

        if (! $user->is_two_factor_enabled) {
            $barcode = $this->google2FA->getQRCodeInline(
                $this->config->get('app.name'),
                $user->email,
                $user->two_factor_secret_key
            );
        }

        return $this->responseFactory->view('2fa.status', compact('user', 'barcode'));
    }
}
