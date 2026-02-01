<?php

namespace Hexlet\Code;

class UrlCheck
{
    private int $id;
    private int $urlId;
    private string $createdAt;


    public function getId(): int
    {
        return $this->id;
    }

    public function getUrlId(): int
    {
        return $this->urlId;
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
