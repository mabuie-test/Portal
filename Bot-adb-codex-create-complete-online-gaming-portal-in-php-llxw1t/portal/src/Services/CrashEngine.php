<?php

declare(strict_types=1);

namespace App\Services;

final class CrashEngine
{
    /** @var array<string, array<string,mixed>> */
    private array $gameConfigs;

    public function __construct(private readonly ProvablyFairService $fairService)
    {
        $this->gameConfigs = require __DIR__ . '/../../config/games.php';
    }

    public function config(string $shortcode): array
    {
        return $this->gameConfigs[$shortcode] ?? throw new \InvalidArgumentException('Game not found');
    }

    public function startRound(string $shortcode, ?string $clientSeed = null, int $nonce = 1): array
    {
        $game = $this->config($shortcode);
        $serverSeed = $this->fairService->generateServerSeed();
        $clientSeed = $clientSeed ?: bin2hex(random_bytes(8));
        $calc = $this->fairService->deriveCrashMultiplier($serverSeed, $clientSeed, $nonce, (float) $game['house_edge']);

        return [
            'game' => $game,
            'server_seed_plain' => $serverSeed,
            'server_seed_hash' => $this->fairService->hashSeed($serverSeed),
            'client_seed' => $clientSeed,
            'nonce' => $nonce,
            'crash_multiplier' => $calc['multiplier'],
            'proof' => $calc,
        ];
    }
}
