<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

class PasskeyGenerator
{
    public function generateUniquePasskey(): string
    {
        do {
            $passkey = $this->generatePasskey();
            $user = $this->getUserWithTheSpecifiedPasskey($passkey);
        } while (null !== $user);

        return $passkey;
    }

    protected function generatePasskey(): string
    {
        return bin2hex(random_bytes(32));
    }

    protected function getUserWithTheSpecifiedPasskey(string $passkey): ?User
    {
        return User::where('passkey', '=', $passkey)->select('passkey')->first();
    }
}
