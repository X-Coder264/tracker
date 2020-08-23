<?php

declare(strict_types=1);

namespace App\Presenters\Announce;

final class User
{
    private int $id;
    private string $slug;
    private int $uploaded;
    private int $downloaded;
    private bool $banned;

    public function __construct(int $id, string $slug, int $uploaded, int $downloaded, bool $banned)
    {
        $this->id = $id;
        $this->slug = $slug;
        $this->uploaded = $uploaded;
        $this->downloaded = $downloaded;
        $this->banned = $banned;
    }

    public static function createFromSelfWithUpdatedUploadedAndDownloadedStats(self $user, int $uploaded, int $downloaded): self
    {
        return new self($user->getId(), $user->getSlug(), $uploaded, $downloaded, $user->isBanned());
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getUploaded(): int
    {
        return $this->uploaded;
    }

    public function getDownloaded(): int
    {
        return $this->downloaded;
    }

    public function isBanned(): bool
    {
        return $this->banned;
    }
}
