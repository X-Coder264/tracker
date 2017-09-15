<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\User;
use App\Services\PasskeyGenerator;

class UserObserver
{
    /**
     * @var PasskeyGenerator
     */
    private $passkeyGenerator;

    public function __construct(PasskeyGenerator $passkeyGenerator)
    {
        $this->passkeyGenerator = $passkeyGenerator;
    }

    public function creating(User $user): void
    {
        $user->passkey = $this->passkeyGenerator->generateUniquePasskey();
    }
}
