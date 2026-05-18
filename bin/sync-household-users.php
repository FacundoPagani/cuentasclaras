<?php

declare(strict_types=1);

use App\Core\Database;

require dirname(__DIR__) . '/bootstrap.php';

$users = [
    [
        'username' => getenv('INIT_USER_USERNAME') ?: 'facu',
        'password' => getenv('INIT_USER_PASSWORD') ?: '132456',
        'name' => getenv('INIT_USER_NAME') ?: 'Facu',
    ],
    [
        'username' => getenv('INIT_SECOND_USER_USERNAME') ?: 'judi',
        'password' => getenv('INIT_SECOND_USER_PASSWORD') ?: '132456',
        'name' => getenv('INIT_SECOND_USER_NAME') ?: 'Judi',
    ],
];

$db = Database::connection();
$db->beginTransaction();

try {
    $db->exec('UPDATE users SET is_active = 0, updated_at = CURRENT_TIMESTAMP');

    $stmt = $db->prepare(
        'INSERT INTO users (username, password_hash, name, is_active)
         VALUES (:username, :password_hash, :name, 1)
         ON CONFLICT(username) DO UPDATE SET
             password_hash = excluded.password_hash,
             name = excluded.name,
             is_active = 1,
             updated_at = CURRENT_TIMESTAMP'
    );

    foreach ($users as $user) {
        $stmt->execute([
            'username' => $user['username'],
            'password_hash' => password_hash($user['password'], PASSWORD_DEFAULT),
            'name' => $user['name'],
        ]);
    }

    $db->commit();
    echo "Usuarios activos sincronizados: {$users[0]['username']} y {$users[1]['username']}.\n";
} catch (Throwable $exception) {
    $db->rollBack();
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
