<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class RoundRepository
{
    public function create(array $payload): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO rounds (game_id, server_seed_hash, server_seed_plain_encrypted, client_seed, nonce, crash_multiplier, resultado_raw, started_at, finished_at) VALUES (:game_id,:seed_hash,:seed_encrypted,:client_seed,:nonce,:multiplier,:raw,NOW(),NULL)'
        );
        $stmt->execute([
            'game_id' => $payload['game_id'],
            'seed_hash' => $payload['server_seed_hash'],
            'seed_encrypted' => $payload['server_seed_plain_encrypted'],
            'client_seed' => $payload['client_seed'],
            'nonce' => $payload['nonce'],
            'multiplier' => $payload['crash_multiplier'],
            'raw' => json_encode($payload['resultado_raw'], JSON_THROW_ON_ERROR),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function finish(int $roundId): void
    {
        $stmt = Database::connection()->prepare('UPDATE rounds SET finished_at = NOW() WHERE round_id = :id');
        $stmt->execute(['id' => $roundId]);
    }

    public function latestByGame(int $gameId, int $limit = 50): array
    {
        $stmt = Database::connection()->prepare('SELECT round_id, game_id, server_seed_hash, client_seed, nonce, crash_multiplier, started_at, finished_at FROM rounds WHERE game_id = :game_id ORDER BY round_id DESC LIMIT ' . (int) $limit);
        $stmt->execute(['game_id' => $gameId]);
        return $stmt->fetchAll();
    }

    public function find(int $roundId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM rounds WHERE round_id=:id LIMIT 1');
        $stmt->execute(['id' => $roundId]);
        return $stmt->fetch() ?: null;
    }
}
