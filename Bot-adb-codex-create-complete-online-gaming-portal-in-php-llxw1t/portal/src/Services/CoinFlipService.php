<?php

declare(strict_types=1);

namespace App\Services;

final class CoinFlipService
{
    public function __construct(
        private readonly ProvablyFairService $fairService,
        private readonly WalletService $walletService,
    ) {
    }

    public function play(int $userId, float $amount, string $choice, ?string $clientSeed = null): array
    {
        $choice = strtolower(trim($choice));
        if (!in_array($choice, ['cara', 'coroa'], true)) {
            throw new \InvalidArgumentException('Escolha inválida');
        }

        if ($amount < 5) {
            throw new \InvalidArgumentException('Montante inválido');
        }

        $serverSeed = $this->fairService->generateServerSeed();
        $serverSeedHash = $this->fairService->hashSeed($serverSeed);
        $clientSeed = $clientSeed ?: bin2hex(random_bytes(8));
        $nonce = random_int(1, 1000000);
        $calc = $this->fairService->deriveCrashMultiplier($serverSeed, $clientSeed, $nonce, 0.01);
        $result = ((int) floor($calc['int_value'])) % 2 === 0 ? 'cara' : 'coroa';
        $won = $choice === $result;

        // aposta debitada sempre
        $this->walletService->debit($userId, $amount, 'coinflip-bet-' . $nonce, 'coinflip', [
            'choice' => $choice,
            'client_seed' => $clientSeed,
            'server_seed_hash' => $serverSeedHash,
        ]);

        $payout = 0.0;
        if ($won) {
            // payout justo aproximado com 1% edge: 1.98x
            $payout = round($amount * 1.98, 2);
            $this->walletService->credit($userId, $payout, 'coinflip-win-' . $nonce, 'coinflip', [
                'result' => $result,
                'choice' => $choice,
                'server_seed_hash' => $serverSeedHash,
            ]);
        }

        return [
            'choice' => $choice,
            'result' => $result,
            'won' => $won,
            'amount' => $amount,
            'payout' => $payout,
            'server_seed_hash' => $serverSeedHash,
            'server_seed_revealed' => $serverSeed,
            'client_seed' => $clientSeed,
            'nonce' => $nonce,
            'hmac' => $calc['hmac'],
        ];
    }
}
