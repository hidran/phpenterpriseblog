<?php

declare(strict_types=1);

namespace App\Console;

use PDO;
use RuntimeException;

final class MigrateCommand
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $migrationsDir,
    ) {
    }

    /**
     * @param array<int, string> $args
     */
    public function __invoke(array $args): int
    {
        if (in_array('--fresh', $args, true)) {
            $this->dropAll();
        }

        $this->ensureTracker();
        $applied = $this->appliedSet();
        $files   = $this->files();

        $pending = array_filter($files, static fn (string $f) => !isset($applied[basename($f)]));
        if ($pending === []) {
            fwrite(STDOUT, "No pending migrations.\n");
            return 0;
        }

        foreach ($pending as $file) {
            $this->apply($file);
        }
        fwrite(STDOUT, "Applied " . count($pending) . " migration(s).\n");
        return 0;
    }

    private function ensureTracker(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations (filename VARCHAR(255) PRIMARY KEY, applied_at DATETIME NOT NULL) '
            . 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    /**
     * @return array<string, true>
     */
    private function appliedSet(): array
    {
        $rows = $this->pdo->query('SELECT filename FROM migrations')?->fetchAll(PDO::FETCH_COLUMN) ?: [];
        return array_fill_keys($rows, true);
    }

    /**
     * @return list<string>
     */
    private function files(): array
    {
        $files = glob($this->migrationsDir . '/*.sql') ?: [];
        sort($files);
        return $files;
    }

    private function apply(string $file): void
    {
        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new RuntimeException("Cannot read {$file}");
        }
        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec($sql);
            $stm = $this->pdo->prepare('INSERT INTO migrations (filename, applied_at) VALUES (:f, NOW())');
            $stm->execute(['f' => basename($file)]);
            $this->pdo->commit();
            fwrite(STDOUT, "Applied: " . basename($file) . "\n");
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function dropAll(): void
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $this->pdo->exec('DROP TABLE IF EXISTS post_comments, posts, users, migrations');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        fwrite(STDOUT, "Dropped all tables.\n");
    }
}
