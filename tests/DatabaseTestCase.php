<?php

namespace Bazaar\Tests;

use Bazaar\Core\Database;
use Bazaar\Core\Migrator;
use Bazaar\Repositories\CategoryRepository;
use Bazaar\Repositories\ProductRepository;
use PDO;
use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    protected Database $db;

    protected function setUp(): void
    {
        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');

        $this->db = Database::fromPdo($pdo);
        Database::swap($this->db);
        (new Migrator($this->db, dirname(__DIR__) . '/database/migrations'))->run();

        $_SESSION = [];
    }

    protected function seedProduct(string $name, int $priceCents, int $stock): int
    {
        $categories = new CategoryRepository($this->db);
        $products = new ProductRepository($this->db);
        $categoryId = $categories->create($name . ' cat', slugify($name . ' cat'), '');
        return $products->create([
            'categoryId' => $categoryId,
            'name' => $name,
            'slug' => slugify($name),
            'description' => '',
            'priceCents' => $priceCents,
            'stockQuantity' => $stock,
            'imageUrl' => '',
            'isActive' => true,
        ]);
    }
}
