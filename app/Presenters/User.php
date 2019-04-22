<?php

declare(strict_types=1);

namespace App\Presenters;

final class User
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $slug;
    /**
     * @var int
     */
    private $updated;
    /**
     * @var int
     */
    private $downloaded;
    /**
     * @var bool
     */
    private $banned;
    /**
     * @var string|null
     */
    private $passkey;

    public function __construct(int $id, string $slug, int $updated, int $downloaded, bool $banned, string $passkey)
    {
        $this->id = $id;
        $this->slug = $slug;
        $this->updated = $updated;
        $this->downloaded = $downloaded;
        $this->banned = $banned;
        $this->passkey = $passkey;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getUpdated(): int
    {
        return $this->updated;
    }

    public function getDownloaded(): int
    {
        return $this->downloaded;
    }

    public function isBanned(): bool
    {
        return $this->banned;
    }

    public function getPasskey(): ?string
    {
        return $this->passkey;
    }

    public function setUpdated(int $updated): self
    {
        $this->updated = $updated;

        return $this;
    }

    public function setDownloaded(int $downloaded): self
    {
        $this->downloaded = $downloaded;

        return $this;
    }
}
