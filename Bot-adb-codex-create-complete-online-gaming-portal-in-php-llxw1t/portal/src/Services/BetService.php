<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\BetRepository;
use Throwable;

final class BetService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly BetRepository $bets,
    ) {
    }

    public function placeBet(int $roundId, int $userId, float $amount, ?float $autoCashout = null): int
    {
        if ($amount < 5) {
            throw new \InvalidArgumentException('Aposta mínima é 5 MTS');
        }
        $this->walletService->debit($userId, $amount, 'bet-' . $roundId . '-' . $userId . '-' . microtime(true), 'game', ['round_id' => $roundId]);
        return $this->bets->place($roundId, $userId, $amount, $autoCashout);
    }

    public function cashout(int $betId, float $cashoutMultiplier): array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $bet = $this->bets->lock($betId);
            if (!$bet || !in_array($bet['status'], ['placed', 'pending'], true)) {
                throw new \RuntimeException('Aposta inválida para cashout');
            }
            $payout = round((float) $bet['amount'] * $cashoutMultiplier, 2);
            $this->bets->markCashedOut($betId, $cashoutMultiplier);
            $pdo->commit();

            $this->walletService->credit((int) $bet['user_id'], $payout, 'cashout-' . $betId, 'game', ['bet_id' => $betId, 'multiplier' => $cashoutMultiplier]);
            return ['bet_id' => $betId, 'payout' => $payout, 'multiplier' => $cashoutMultiplier];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function settleRoundLosses(int $roundId): void
    {
        $this->bets->markLostForRound($roundId);
    }
}
