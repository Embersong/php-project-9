<?php

namespace Hexlet\Code;

class Url
{
    private int $id;
    private string $name;
    private string $created_at;


    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreatedAt(): string
    {
        return $this->created_at;
    }


    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setCreatedAt(string $created_at): void
    {
        $this->created_at = $created_at;
    }
}
