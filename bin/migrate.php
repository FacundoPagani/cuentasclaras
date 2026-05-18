<?php

declare(strict_types=1);

use App\Core\Database;

$config = require dirname(__DIR__) . '/bootstrap.php';
$db = Database::connection();
$schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');

if ($schema === false) {
    fwrite(STDERR, "No se pudo leer database/schema.sql\n");
    exit(1);
}

$db->exec($schema);
echo "SQLite inicializado en {$config['database_path']}\n";
