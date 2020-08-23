<?php

declare(strict_types=1);

namespace App\Presenters\Announce\Response;

use InvalidArgumentException;

final class Peer
{
    private string $ip;
    private bool $isIPv6;
    private int $port;
    private string $id;

    public function __construct(string $ip, bool $isIPv6, int $port, string $id)
    {
        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException(sprintf('The given port %d is not valid.', $port));
        }

        $this->ip = $ip;
        $this->isIPv6 = $isIPv6;
        $this->port = $port;
        $this->id = $id;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function isIPv6(): bool
    {
        return $this->isIPv6;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
