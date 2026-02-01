<?php

namespace Hexlet\Code;

use Carbon\Carbon;

class UrlChecksRepository
{
    private \PDO $connection;
    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }

    public function save(UrlCheck $check): void
    {
        $sql = "INSERT INTO url_checks (url_id, status_code, h1, title, description, created_at)
        VALUES (:urlId, :statusCode, :h1, :title, :description, :createdAt);";
        $stmt = $this->connection->prepare($sql);
        $createdAt = Carbon::now();
        $urlId = $check->getUrlId();
        $statusCode = 200;
        $h1 = 'test';
        $title = 'test';
        $description = 'test';
        $stmt->bindParam(':urlId', $urlId);
        $stmt->bindParam(':statusCode', $statusCode);
        $stmt->bindParam(':h1', $h1);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':createdAt', $createdAt);
        $stmt->execute();
        $id = (int) $this->connection->lastInsertId();
        $check->setId($id);
        $check->setCreatedAt($createdAt);
    }

    public function findByUrlId(int $urlId): array
    {
        $result = [];

        $sql = "SELECT * FROM url_checks WHERE url_id = ? ORDER BY id DESC";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$urlId]);

        while ($row = $stmt->fetch()) {
            $check = new UrlCheck();
            $check->setId($row['id']);
            $check->setUrlId($row['url_id']);
            $check->setCreatedAt($row['created_at']);
            $result[] = $check;
        }

        return $result;
    }

    public function findLatestChecks(): array
    {
        $sql = "SELECT DISTINCT ON (url_id) * from url_checks order by url_id DESC, id DESC";

        $stmt = $this->connection->query($sql);

        $latestChecks = [];

        while ($row = $stmt->fetch()) {
            $check = new UrlCheck();
            $url_id = $row['url_id'];
            $check->setId($row['id']);
            $check->setUrlId($url_id);
            $check->setCreatedAt($row['created_at']);
            $latestChecks[$url_id] = $check;
        }

        return $latestChecks;
    }
}
