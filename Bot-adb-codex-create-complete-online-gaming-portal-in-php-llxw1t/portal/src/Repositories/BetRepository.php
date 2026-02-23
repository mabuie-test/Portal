<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class BetRepository
{
    public function place(int $roundId, int $userId, float $amount, ?float $autoCashout): int
    {
        $stmt = Database::connection()->prepare('INSERT INTO bets (round_id,user_id,amount,auto_cashout,status,placed_at) VALUES (:round_id,:user_id,:amount,:auto_cashout,:status,NOW())');
        $stmt->execute([
            'round_id' => $roundId,
            'user_id' => $userId,
            'amount' => $amount,
            'auto_cashout' => $autoCashout,
            'status' => 'placed',
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function lock(int $betId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM bets WHERE bet_id=:id FOR UPDATE');
        $stmt->execute(['id' => $betId]);
        return $stmt->fetch() ?: null;
    }

    public function markCashedOut(int $betId, float $multiplier): void
    {
        $stmt = Database::connection()->prepare('UPDATE bets SET status=:status, cashout_multiplier=:multiplier, cashed_out_at=NOW() WHERE bet_id=:id');
        $stmt->execute(['status' => 'cashed_out', 'multiplier' => $multiplier, 'id' => $betId]);
    }

    public function markLostForRound(int $roundId): void
    {
        $stmt = Database::connection()->prepare("UPDATE bets SET status='lost' WHERE round_id=:round_id AND status IN ('placed','pending')");
        $stmt->execute(['round_id' => $roundId]);
    }
}
