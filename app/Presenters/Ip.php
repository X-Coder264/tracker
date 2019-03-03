<?php

declare(strict_types=1);

namespace App\Presenters;

class Ip
{
    private $ip;
    private $port;
    private $isV4;

    public function __construct(string $ip, ?int $port, bool $isV4)
    {
        $this->ip = $ip;
        $this->port = $port;
        $this->isV4 = $isV4;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function hasPort(): bool
    {
        return null !== $this->port;
    }

    public function isV4(): bool
    {
        return $this->isV4;
    }

    public function isV6(): bool
    {
        return !$this->isV4;
    }
}
