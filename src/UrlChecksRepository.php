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
        $statusCode = $check->getStatusCode();
        $h1 = $check->getH1();
        $title = $check->getTitle();
        $description = $check->getDescription();

        $stmt->execute([
            ':urlId' => $urlId,
            ':statusCode' => $statusCode,
            ':h1' => $h1,
            ':title' => $title,
            ':description' => $description,
            ':createdAt' => $createdAt
        ]);
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
            $check = new UrlCheck(
                $row['status_code'],
                $row['title'],
                $row['h1'],
                $row['description']
            );
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

        /** @var \PDOStatement $stmt */
        $stmt = $this->connection->query($sql);

        $latestChecks = [];

        while ($row = $stmt->fetch()) {
            $check = new UrlCheck(
                $row['status_code'],
                $row['title'],
                $row['h1'],
                $row['description']
            );
            $url_id = $row['url_id'];
            $check->setId($row['id']);
            $check->setUrlId($url_id);
            $check->setCreatedAt($row['created_at']);
            $latestChecks[$url_id] = $check;
        }

        return $latestChecks;
    }
}
