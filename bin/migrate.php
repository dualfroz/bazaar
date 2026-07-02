<?php

use Bazaar\Core\Config;
use Bazaar\Core\Database;
use Bazaar\Core\Migrator;

require dirname(__DIR__) . '/vendor/autoload.php';

Config::load(basePath('.env'));

if (Config::get('DB_DRIVER') === 'sqlite') {
    $path = (string) Config::get('DB_DATABASE');
    if ($path !== ':memory:' && !str_starts_with($path, '/')) {
        $path = basePath($path);
    }
    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0775, true);
    }
    if (!is_file($path)) {
        touch($path);
    }
}

$migrator = new Migrator(Database::instance(), basePath('database/migrations'));
$ran = $migrator->run();

if ($ran === []) {
    echo "Nothing to migrate. Schema is up to date.\n";
} else {
    echo "Applied migrations:\n";
    foreach ($ran as $name) {
        echo "  - {$name}\n";
    }
}
