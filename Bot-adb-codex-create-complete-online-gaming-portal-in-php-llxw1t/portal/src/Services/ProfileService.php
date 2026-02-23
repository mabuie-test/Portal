<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class ProfileService
{
    public function getProfile(int $userId): array
    {
        $stmt = Database::connection()->prepare('SELECT id, user_code, full_name, email, phone, birth_date, avatar_url, status, preferences_json, created_at FROM users WHERE id=:id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        if (!$user) {
            throw new \RuntimeException('Utilizador não encontrado');
        }

        $w = Database::connection()->prepare('SELECT balance, currency FROM wallets WHERE user_id=:id LIMIT 1');
        $w->execute(['id' => $userId]);
        $wallet = $w->fetch() ?: ['balance' => 0, 'currency' => 'MZN'];

        $user['wallet'] = ['balance' => (float) $wallet['balance'], 'currency' => $wallet['currency']];
        return $user;
    }

    public function updatePreferences(int $userId, array $prefs): void
    {
        $allowed = [
            'display_name' => (string) ($prefs['display_name'] ?? ''),
            'theme' => (string) ($prefs['theme'] ?? 'dark'),
            'sound_enabled' => (bool) ($prefs['sound_enabled'] ?? true),
            'avatar_url' => (string) ($prefs['avatar_url'] ?? ''),
        ];
        $stmt = Database::connection()->prepare('UPDATE users SET preferences_json=:prefs, avatar_url=:avatar WHERE id=:id');
        $stmt->execute(['prefs' => json_encode($allowed, JSON_THROW_ON_ERROR), 'avatar' => ($allowed['avatar_url'] ?: null), 'id' => $userId]);
    }

    public function betHistory(int $userId, int $limit = 100): array
    {
        $sql = 'SELECT b.bet_id, b.amount, b.status, b.auto_cashout, b.cashout_multiplier, b.placed_at, r.round_id, g.name AS game_name FROM bets b JOIN rounds r ON r.round_id=b.round_id JOIN games g ON g.id=r.game_id WHERE b.user_id=:uid ORDER BY b.bet_id DESC LIMIT ' . (int) $limit;
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function withdrawals(int $userId, int $limit = 100): array
    {
        $sql = "SELECT t.transaction_id, t.amount, t.status, t.reference, t.created_at FROM transactions t JOIN wallets w ON w.wallet_id=t.wallet_id WHERE w.user_id=:uid AND t.type='debit' AND (t.external_provider='withdrawal' OR JSON_EXTRACT(t.metadata, '$.category')='withdrawal') ORDER BY t.transaction_id DESC LIMIT " . (int) $limit;
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }
}
