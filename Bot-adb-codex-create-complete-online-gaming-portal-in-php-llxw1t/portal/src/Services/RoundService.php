<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use App\Core\Database;
use App\Repositories\RoundRepository;

final class RoundService
{
    public function __construct(
        private readonly CrashEngine $engine,
        private readonly ProvablyFairService $fair,
        private readonly RoundRepository $rounds,
    ) {
    }

    public function startRound(string $shortcode, int $gameId, int $nonce = 1, ?string $clientSeed = null): array
    {
        $preview = $this->engine->startRound($shortcode, $clientSeed, $nonce);
        $encrypted = $this->fair->encryptSeed($preview['server_seed_plain'], (string) Env::get('MASTER_SEED_KEY', 'dev-key'));

        $roundId = $this->rounds->create([
            'game_id' => $gameId,
            'server_seed_hash' => $preview['server_seed_hash'],
            'server_seed_plain_encrypted' => $encrypted,
            'client_seed' => $preview['client_seed'],
            'nonce' => $preview['nonce'],
            'crash_multiplier' => $preview['crash_multiplier'],
            'resultado_raw' => $preview['proof'],
        ]);

        $proofStmt = Database::connection()->prepare('INSERT INTO provably_proofs (round_id, published_hash, revealed_seed, client_seed, nonce, hmac_result, calculation_steps, created_at) VALUES (:round_id,:published_hash,:revealed_seed,:client_seed,:nonce,:hmac,:steps,NOW())');
        $proofStmt->execute([
            'round_id' => $roundId,
            'published_hash' => $preview['server_seed_hash'],
            'revealed_seed' => $preview['server_seed_plain'],
            'client_seed' => $preview['client_seed'],
            'nonce' => $preview['nonce'],
            'hmac' => $preview['proof']['hmac'],
            'steps' => json_encode($preview['proof'], JSON_THROW_ON_ERROR),
        ]);

        $preview['round_id'] = $roundId;
        return $preview;
    }

    public function finishRound(int $roundId): void
    {
        $this->rounds->finish($roundId);
    }
}
