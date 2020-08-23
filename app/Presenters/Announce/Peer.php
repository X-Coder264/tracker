<?php

declare(strict_types=1);

namespace App\Presenters\Announce;

use Carbon\CarbonImmutable;

final class Peer
{
    private ?int $id;
    private int $uploaded;
    private int $downloaded;
    private int $left;
    private string $peerId;
    private string $ipAddress;
    private bool $isIPv6;
    private int $port;
    private int $version;
    private string $userAgent;
    private ?string $key;
    private CarbonImmutable $createdAt;
    private CarbonImmutable $updatedAt;

    public function __construct(
        ?int $id,
        int $uploaded,
        int $downloaded,
        int $left,
        string $peerId,
        string $ipAddress,
        bool $isIPv6,
        int $port,
        int $version,
        string $userAgent,
        ?string $key,
        CarbonImmutable $createdAt,
        CarbonImmutable $updatedAt
    ) {
        $this->id = $id;
        $this->uploaded = $uploaded;
        $this->downloaded = $downloaded;
        $this->left = $left;
        $this->peerId = $peerId;
        $this->ipAddress = $ipAddress;
        $this->isIPv6 = $isIPv6;
        $this->port = $port;
        $this->version = $version;
        $this->userAgent = $userAgent;
        $this->key = $key;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function withId(int $id): self
    {
        $peer = clone $this;
        $peer->id = $id;

        return $peer;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUploaded(): int
    {
        return $this->uploaded;
    }

    public function getDownloaded(): int
    {
        return $this->downloaded;
    }

    public function getLeft(): int
    {
        return $this->left;
    }

    public function isSeeder(): bool
    {
        return 0 === $this->left;
    }

    public function getPeerId(): string
    {
        return $this->peerId;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function isIPv6(): bool
    {
        return $this->isIPv6;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function getCreatedAt(): CarbonImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): CarbonImmutable
    {
        return $this->updatedAt;
    }
}
