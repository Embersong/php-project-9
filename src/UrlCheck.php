<?php

namespace Hexlet\Code;

class UrlCheck
{
    private int $id;
    private int $urlId;
    private int $statusCode;
    private string $title;
    private string $h1;
    private string $description;
    private string $createdAt;

    public function __construct(int $statusCode, string $title, string $h1, string $description)
    {
        $this->statusCode = $statusCode;
        $this->title = $title;
        $this->h1 = $h1;
        $this->description = $description;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUrlId(): int
    {
        return $this->urlId;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getH1(): string
    {
        return $this->h1;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setUrlId(int $id): void
    {
        $this->urlId = $id;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
