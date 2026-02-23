<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Security\Password;

final class AuthService
{
    public function register(string $name, string $email, string $phone, string $birthDate, string $password): int
    {
        $this->assertAdult($birthDate);

        $stmt = Database::connection()->prepare('SELECT id FROM users WHERE email=:email OR phone=:phone LIMIT 1');
        $stmt->execute(['email' => strtolower(trim($email)), 'phone' => trim($phone)]);
        if ($stmt->fetch()) {
            throw new \RuntimeException('Email ou telemóvel já em uso');
        }

        $ins = Database::connection()->prepare('INSERT INTO users (full_name,email,phone,birth_date,password_hash,status,created_at,role_id) VALUES (:full_name,:email,:phone,:birth_date,:password_hash,:status,NOW(),1)');
        $ins->execute([
            'full_name' => trim($name),
            'email' => strtolower(trim($email)),
            'phone' => trim($phone),
            'birth_date' => $birthDate,
            'password_hash' => Password::hash($password),
            'status' => 'active',
        ]);

        $id = (int) Database::connection()->lastInsertId();
        $userCode = str_pad((string) $id, 5, "0", STR_PAD_LEFT);
        $u = Database::connection()->prepare('UPDATE users SET user_code=:code WHERE id=:id');
        $u->execute(['code' => $userCode, 'id' => $id]);
        return $id;
    }

    public function login(string $email, string $password): array
    {
        $stmt = Database::connection()->prepare('SELECT id, user_code, full_name, email, phone, birth_date, avatar_url, password_hash, status, preferences_json FROM users WHERE email=:email LIMIT 1');
        $stmt->execute(['email' => strtolower(trim($email))]);
        $user = $stmt->fetch();
        if (!$user || !Password::verify($password, (string) $user['password_hash'])) {
            throw new \RuntimeException('Credenciais inválidas');
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_email'] = $user['email'];

        unset($user['password_hash']);
        return $user;
    }

    public function requestPasswordReset(string $email): void
    {
        $token = bin2hex(random_bytes(24));
        $stmt = Database::connection()->prepare('UPDATE users SET reset_token=:token, reset_token_expires_at=DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE email=:email');
        $stmt->execute(['token' => $token, 'email' => strtolower(trim($email))]);
        // produção: enviar email com token
    }

    public function resetPassword(string $email, string $token, string $newPassword): void
    {
        $stmt = Database::connection()->prepare('SELECT id FROM users WHERE email=:email AND reset_token=:token AND reset_token_expires_at > NOW() LIMIT 1');
        $stmt->execute(['email' => strtolower(trim($email)), 'token' => $token]);
        $user = $stmt->fetch();
        if (!$user) {
            throw new \RuntimeException('Token inválido ou expirado');
        }

        $upd = Database::connection()->prepare('UPDATE users SET password_hash=:hash, reset_token=NULL, reset_token_expires_at=NULL WHERE id=:id');
        $upd->execute(['hash' => Password::hash($newPassword), 'id' => $user['id']]);
    }

    private function assertAdult(string $birthDate): void
    {
        $birth = new \DateTimeImmutable($birthDate);
        $limit = (new \DateTimeImmutable('now'))->modify('-18 years');
        if ($birth > $limit) {
            throw new \RuntimeException('Apenas maiores de 18 anos');
        }
    }
}
