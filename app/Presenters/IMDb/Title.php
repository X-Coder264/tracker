<?php

declare(strict_types=1);

namespace App\Presenters\IMDb;

class Title
{
    private $id;

    private $name;

    private $rating;

    private $genres;

    private $plotOutline;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getRating(): string
    {
        return $this->rating;
    }

    public function setRating(string $rating): self
    {
        $this->rating = $rating;

        return $this;
    }

    public function getGenres(): array
    {
        return $this->genres;
    }

    public function setGenres(array $genres): self
    {
        $this->genres = $genres;

        return $this;
    }

    public function getPlotOutline(): string
    {
        return $this->plotOutline;
    }

    public function setPlotOutline(string $plotOutline): self
    {
        $this->plotOutline = $plotOutline;

        return $this;
    }
}
