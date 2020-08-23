<?php

declare(strict_types=1);

namespace App\Presenters\Announce;

use InvalidArgumentException;

final class IpAddress
{
    private string $ip;
    private bool $isIPv6;

    public function __construct(string $ip)
    {
        $this->ip = $ip;

        if ($this->isValidIPv4Address($ip)) {
            $this->isIPv6 = false;
        } elseif ($this->isValidIPv6Address($ip)) {
            $this->isIPv6 = true;
        } else {
            throw new InvalidArgumentException(sprintf('Invalid IP address given - "%s"', $ip));
        }
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function isIPv6(): bool
    {
        return $this->isIPv6;
    }

    private function isValidIPv4Address(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return true;
        }

        return false;
    }

    private function isValidIPv6Address(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return true;
        }

        return false;
    }
}
