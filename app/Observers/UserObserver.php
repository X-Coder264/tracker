<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use App\Services\PasskeyGenerator;

class UserObserver
{
    /**
     * @var PasskeyGenerator
     */
    private $passkeyGenerator;

    /**
     * @var Google2FA
     */
    private $google2FA;

    public function __construct(PasskeyGenerator $passkeyGenerator, Google2FA $google2FA)
    {
        $this->passkeyGenerator = $passkeyGenerator;
        $this->google2FA = $google2FA;
    }

    public function creating(User $user): void
    {
        $user->passkey = $this->passkeyGenerator->generateUniquePasskey();
        $user->two_factor_secret_key = $this->google2FA->generateSecretKey(32);
    }
}
