<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Auth
{
    public function __construct(private PDO $db, private array $config)
    {
    }

    public function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name($this->config['session_name']);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => (bool) $this->config['session_secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
        $this->enforceTimeout();
    }

    public function attempt(string $username, string $password): bool
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :username AND is_active = 1 LIMIT 1');
        $stmt->execute(['username' => trim($username)]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            password_hash($password, PASSWORD_DEFAULT);
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['last_activity'] = time();

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $update = $this->db->prepare('UPDATE users SET password_hash = :hash, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $update->execute([
                'hash' => password_hash($password, PASSWORD_DEFAULT),
                'id' => (int) $user['id'],
            ]);
        }

        return true;
    }

    public function user(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT id, username, name FROM users WHERE id = :id AND is_active = 1 LIMIT 1');
        $stmt->execute(['id' => (int) $_SESSION['user_id']]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function requireUser(): array
    {
        $user = $this->user();

        if ($user === null) {
            $this->redirect('/login');
        }

        return $user;
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
        }

        session_destroy();
    }

    private function enforceTimeout(): void
    {
        if (empty($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            return;
        }

        if ((time() - (int) $_SESSION['last_activity']) > (int) $this->config['session_lifetime']) {
            $this->logout();
            return;
        }

        $_SESSION['last_activity'] = time();
    }

    private function redirect(string $path): never
    {
        header('Location: ' . $path, true, 302);
        exit;
    }
}
