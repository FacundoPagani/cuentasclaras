<?php

declare(strict_types=1);

use App\Core\Database;

require dirname(__DIR__) . '/bootstrap.php';

$db = Database::connection();
$db->beginTransaction();

try {
    $db->exec('DELETE FROM settlement_user_lines');
    $db->exec('DELETE FROM settlements');
    $db->exec('DELETE FROM credit_card_drafts');
    $db->exec('DELETE FROM monthly_obligations');
    $db->exec('DELETE FROM daily_expenses');

    $db->exec("DELETE FROM sqlite_sequence WHERE name IN ('settlement_user_lines', 'settlements', 'credit_card_drafts', 'monthly_obligations', 'daily_expenses')");

    $db->exec('UPDATE users SET is_active = 0, updated_at = CURRENT_TIMESTAMP');

    $userStmt = $db->prepare(
        'INSERT INTO users (username, password_hash, name, is_active)
         VALUES (:username, :password_hash, :name, 1)
         ON CONFLICT(username) DO UPDATE SET
             password_hash = excluded.password_hash,
             name = excluded.name,
             is_active = 1,
             updated_at = CURRENT_TIMESTAMP'
    );

    foreach ([
        ['username' => getenv('INIT_USER_USERNAME') ?: 'facu', 'password' => getenv('INIT_USER_PASSWORD') ?: '132456', 'name' => getenv('INIT_USER_NAME') ?: 'Facu'],
        ['username' => getenv('INIT_SECOND_USER_USERNAME') ?: 'judi', 'password' => getenv('INIT_SECOND_USER_PASSWORD') ?: '132456', 'name' => getenv('INIT_SECOND_USER_NAME') ?: 'Judi'],
    ] as $user) {
        $userStmt->execute([
            'username' => $user['username'],
            'password_hash' => password_hash($user['password'], PASSWORD_DEFAULT),
            'name' => $user['name'],
        ]);
    }

    $db->commit();
    echo "Datos operativos reiniciados. Usuarios activos sincronizados.\n";
} catch (Throwable $exception) {
    $db->rollBack();
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
