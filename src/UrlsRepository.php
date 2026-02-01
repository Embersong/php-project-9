<?php

namespace Hexlet\Code;

use Carbon\Carbon;

class UrlsRepository
{
    private \PDO $connection;

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }

    public function find(int $id): ?Url
    {
        $sql = "SELECT * FROM urls WHERE id = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id]);
        if ($row = $stmt->fetch()) {
            $url = new Url($row['name']);
            $url->setId($row['id']);
            $url->setCreatedAt($row['created_at']);
            return $url;
        }

        return null;
    }

    public function all(): array
    {
        $urls = [];
        $sql = "SELECT * FROM urls";

        /** @var \PDOStatement $stmt */
        $stmt = $this->connection->query($sql);

        while ($row = $stmt->fetch()) {
            $url = new Url($row['name']);
            $url->setId($row['id']);
            $url->setCreatedAt($row['created_at']);
            $urls[] = $url;
        }

        return $urls;
    }

    public function findByName(string $name): ?Url
    {
        $sql = "SELECT * FROM urls WHERE name = ?";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$name]);
        if ($row = $stmt->fetch()) {
            $url = new Url($row['name']);
            $url->setId($row['id']);
            $url->setCreatedAt($row['created_at']);
            return $url;
        }

        return null;
    }

    public function save(Url $url): void
    {
        $sql = "INSERT INTO urls (name, created_at) VALUES (:name, :created_at)";
        $stmt = $this->connection->prepare($sql);
        $name = $url->getName();
        $created_at = Carbon::now();
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':created_at', $created_at);
        $stmt->execute();
        $id = (int) $this->connection->lastInsertId();
        $url->setId($id);
        $url->setCreatedAt($created_at);
    }
}
