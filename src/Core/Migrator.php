<?php

namespace Bazaar\Core;

final class Migrator
{
    public function __construct(
        private readonly Database $db,
        private readonly string $migrationsPath
    ) {
    }

    public function run(): array
    {
        $this->db->statement(
            'CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                appliedAt TEXT NOT NULL
            )'
        );

        $applied = array_column(
            $this->db->select('SELECT name FROM migrations'),
            'name'
        );

        $ran = [];
        foreach ($this->files() as $name => $path) {
            if (in_array($name, $applied, true)) {
                continue;
            }
            $this->db->pdo()->exec((string) file_get_contents($path));
            $this->db->insert(
                'INSERT INTO migrations (name, appliedAt) VALUES (?, ?)',
                [$name, gmdate('c')]
            );
            $ran[] = $name;
        }

        return $ran;
    }

    private function files(): array
    {
        $files = glob($this->migrationsPath . '/*.sql') ?: [];
        sort($files);
        $indexed = [];
        foreach ($files as $file) {
            $indexed[basename($file)] = $file;
        }
        return $indexed;
    }
}
