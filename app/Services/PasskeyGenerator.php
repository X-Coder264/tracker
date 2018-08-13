<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

class PasskeyGenerator
{
    /**
     * @return string
     */
    public function generateUniquePasskey(): string
    {
        do {
            $passkey = $this->generatePasskey();
            $user = $this->getUserWithTheSpecifiedPasskey($passkey);
        } while (null !== $user);

        return $passkey;
    }

    /**
     * @return string
     */
    protected function generatePasskey(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * @param string $passkey
     *
     * @return User|null
     */
    protected function getUserWithTheSpecifiedPasskey(string $passkey): ?User
    {
        return User::where('passkey', '=', $passkey)->select('passkey')->first();
    }
}
