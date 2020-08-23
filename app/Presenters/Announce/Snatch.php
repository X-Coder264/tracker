<?php

declare(strict_types=1);

namespace App\Presenters\Announce;

use Carbon\CarbonImmutable;

final class Snatch
{
    private ?int $id;
    private int $uploaded;
    private int $downloaded;
    private int $left;
    private int $seedTime;
    private int $leechTime;
    private int $timesAnnounced;
    private CarbonImmutable $createdAt;
    private CarbonImmutable $updatedAt;
    private ?CarbonImmutable $finishedAt;
    private string $userAgent;

    public function __construct(
        ?int $id,
        int $uploaded,
        int $downloaded,
        int $left,
        int $seedTime,
        int $leechTime,
        int $timesAnnounced,
        CarbonImmutable $createdAt,
        CarbonImmutable $updatedAt,
        ?CarbonImmutable $finishedAt,
        string $userAgent
    ) {
        $this->id = $id;
        $this->uploaded = $uploaded;
        $this->downloaded = $downloaded;
        $this->left = $left;
        $this->seedTime = $seedTime;
        $this->leechTime = $leechTime;
        $this->timesAnnounced = $timesAnnounced;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->finishedAt = $finishedAt;
        $this->userAgent = $userAgent;
    }

    public function withId(int $id): self
    {
        $snatch = clone $this;
        $snatch->id = $id;

        return $snatch;
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

    public function getSeedTime(): int
    {
        return $this->seedTime;
    }

    public function getLeechTime(): int
    {
        return $this->leechTime;
    }

    public function getTimesAnnounced(): int
    {
        return $this->timesAnnounced;
    }

    public function getCreatedAt(): CarbonImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): CarbonImmutable
    {
        return $this->updatedAt;
    }

    public function getFinishedAt(): ?CarbonImmutable
    {
        return $this->finishedAt;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }
}
