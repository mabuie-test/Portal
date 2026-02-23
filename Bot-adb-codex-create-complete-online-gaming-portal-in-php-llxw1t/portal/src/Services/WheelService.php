<?php

declare(strict_types=1);

namespace App\Services;

final class WheelService
{
    /** @var array<int, float> */
    private array $segments = [0.5,0.7,0.8,0.9,1.0,1.1,1.2,1.5,2.0,3.0,5.0];

    public function __construct(
        private readonly ProvablyFairService $fairService,
        private readonly WalletService $walletService,
    ) {
    }

    public function play(int $userId, float $amount, ?string $clientSeed = null): array
    {
        if ($amount < 5) {
            throw new \InvalidArgumentException('Aposta mínima é 5 MTS');
        }

        $serverSeed = $this->fairService->generateServerSeed();
        $serverSeedHash = $this->fairService->hashSeed($serverSeed);
        $clientSeed = $clientSeed ?: bin2hex(random_bytes(8));
        $nonce = random_int(1, 1000000);
        $calc = $this->fairService->deriveCrashMultiplier($serverSeed, $clientSeed, $nonce, 0.02);

        $index = ((int) floor($calc['int_value'])) % count($this->segments);
        $odd = $this->segments[$index];
        $payout = round($amount * $odd, 2);

        $this->walletService->debit($userId, $amount, 'wheel-bet-' . $nonce, 'wheel', [
            'odd' => $odd,
            'server_seed_hash' => $serverSeedHash,
            'client_seed' => $clientSeed,
        ]);

        if ($payout > 0) {
            $this->walletService->credit($userId, $payout, 'wheel-payout-' . $nonce, 'wheel', [
                'odd' => $odd,
                'server_seed_hash' => $serverSeedHash,
            ]);
        }

        return [
            'amount' => $amount,
            'odd' => $odd,
            'payout' => $payout,
            'server_seed_hash' => $serverSeedHash,
            'server_seed_revealed' => $serverSeed,
            'client_seed' => $clientSeed,
            'nonce' => $nonce,
            'hmac' => $calc['hmac'],
            'segments' => $this->segments,
            'segment_index' => $index,
        ];
    }
}
