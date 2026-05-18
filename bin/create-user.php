<?php

declare(strict_types=1);

use App\Core\Database;

require dirname(__DIR__) . '/bootstrap.php';

[$script, $username, $password, $name] = array_pad($argv, 4, null);

if (!$username || !$password || !$name) {
    fwrite(STDERR, "Uso: php bin/create-user.php <usuario> <password> <nombre>\n");
    exit(1);
}

$db = Database::connection();
$stmt = $db->prepare(
    'INSERT INTO users (username, password_hash, name)
     VALUES (:username, :password_hash, :name)
     ON CONFLICT(username) DO UPDATE SET password_hash = excluded.password_hash, name = excluded.name, is_active = 1, updated_at = CURRENT_TIMESTAMP'
);
$stmt->execute([
    'username' => $username,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'name' => $name,
]);

echo "Usuario {$username} listo.\n";
