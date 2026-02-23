<?php

declare(strict_types=1);

namespace App\Services;

final class DiceDuelService
{
    public function __construct(
        private readonly ProvablyFairService $fairService,
        private readonly WalletService $walletService,
    ) {
    }

    public function play(int $userId, float $amount, string $betType, string $selection, ?string $clientSeed = null): array
    {
        if ($amount < 5) {
            throw new \InvalidArgumentException('Aposta mínima é 5 MTS');
        }

        $serverSeed = $this->fairService->generateServerSeed();
        $serverSeedHash = $this->fairService->hashSeed($serverSeed);
        $clientSeed = $clientSeed ?: bin2hex(random_bytes(8));
        $nonce = random_int(1, 1000000);
        $calc = $this->fairService->deriveCrashMultiplier($serverSeed, $clientSeed, $nonce, 0.02);
        $base = (int) floor($calc['int_value']);

        $blue = ($base % 6) + 1;
        $white = (int) (floor($base / 7) % 6) + 1;
        $sum = $blue + $white;

        $won = false;
        $odd = 1.0;
        if ($betType === 'sum') {
            $target = (int) $selection;
            $won = $sum === $target;
            $odd = 5.5;
        } elseif ($betType === 'compare') {
            $won = ($selection === 'blue_gt_white' && $blue > $white) || ($selection === 'white_gt_blue' && $white > $blue);
            $odd = 1.9;
        } elseif ($betType === 'parity') {
            $target = strtolower($selection);
            $won = ($target === 'sum_even' && $sum % 2 === 0) || ($target === 'sum_odd' && $sum % 2 !== 0)
                || ($target === 'blue_even' && $blue % 2 === 0) || ($target === 'blue_odd' && $blue % 2 !== 0)
                || ($target === 'white_even' && $white % 2 === 0) || ($target === 'white_odd' && $white % 2 !== 0);
            $odd = 1.85;
        } else {
            throw new \InvalidArgumentException('Tipo de aposta inválido');
        }

        $payout = $won ? round($amount * $odd, 2) : 0.0;

        $this->walletService->debit($userId, $amount, 'dice-bet-' . $nonce, 'dice_duel', [
            'bet_type' => $betType,
            'selection' => $selection,
            'server_seed_hash' => $serverSeedHash,
        ]);

        if ($payout > 0) {
            $this->walletService->credit($userId, $payout, 'dice-win-' . $nonce, 'dice_duel', [
                'bet_type' => $betType,
                'selection' => $selection,
                'server_seed_hash' => $serverSeedHash,
            ]);
        }

        return [
            'blue_dice' => $blue,
            'white_dice' => $white,
            'sum' => $sum,
            'bet_type' => $betType,
            'selection' => $selection,
            'won' => $won,
            'odd' => $odd,
            'payout' => $payout,
            'server_seed_hash' => $serverSeedHash,
            'server_seed_revealed' => $serverSeed,
            'client_seed' => $clientSeed,
            'nonce' => $nonce,
            'hmac' => $calc['hmac'],
        ];
    }
}
