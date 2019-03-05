<?php

declare(strict_types=1);

namespace App\Presenters\Announce;

use App\Presenters\Ip;

class Data
{
    private $event;

    /**
     * @var string
     */
    private $passKey;

    private $userAgent;

    private $infoHash;

    private $peerId;

    private $downloaded;

    private $uploaded;

    private $left;

    private $numberOfWantedPeers = 50;

    /**
     * @var Ip|null
     */
    private $ipV4;

    /**
     * @var Ip|null
     */
    private $ipV6;

    public function __construct(
        ?string $event,
        string $passKey,
        string $userAgent,
        string $infoHash,
        string $peerId,
        int $downloaded,
        int $uploaded,
        int $left,
        ?int $numberOfWantedPeers,
        ?Ip $ipV4,
        ?Ip $ipV6
    ) {
        $this->event = $event;
        $this->passKey = $passKey;
        $this->userAgent = $userAgent;
        $this->infoHash = $infoHash;
        $this->peerId = $peerId;
        $this->downloaded = $downloaded;
        $this->uploaded = $uploaded;
        $this->left = $left;

        if (null !== $numberOfWantedPeers && $numberOfWantedPeers > 0) {
            $this->numberOfWantedPeers = $numberOfWantedPeers;
        }

        $this->ipV4 = $ipV4;
        $this->ipV6 = $ipV6;
    }

    public function getEvent(): ?string
    {
        return $this->event;
    }

    public function isEvent(): bool
    {
        return null !== $this->event;
    }

    public function getPassKey(): string
    {
        return $this->passKey;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function getInfoHash(): string
    {
        return $this->infoHash;
    }

    public function getPeerId(): string
    {
        return $this->peerId;
    }

    public function getDownloaded(): int
    {
        return $this->downloaded;
    }

    public function getUploaded(): int
    {
        return $this->uploaded;
    }

    public function getLeft(): int
    {
        return $this->left;
    }

    public function getNumberOfWantedPeers(): int
    {
        return $this->numberOfWantedPeers;
    }

    public function getIpV4(): ?Ip
    {
        return $this->ipV4;
    }

    public function getIpV6(): ?Ip
    {
        return $this->ipV6;
    }
}
