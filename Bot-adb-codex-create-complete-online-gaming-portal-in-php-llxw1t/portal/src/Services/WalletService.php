<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use Throwable;

final class WalletService
{
    public function credit(int $userId, float $amount, string $reference, string $provider, array $metadata = []): void
    {
        $this->applyMutation($userId, abs($amount), 'credit', $reference, $provider, $metadata);
    }

    public function debit(int $userId, float $amount, string $reference, string $provider, array $metadata = []): void
    {
        $this->applyMutation($userId, -abs($amount), 'debit', $reference, $provider, $metadata);
    }

    private function applyMutation(int $userId, float $signedAmount, string $type, string $reference, string $provider, array $metadata): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $wallet = $this->lockWallet($pdo, $userId);
            $newBalance = (float) $wallet['balance'] + $signedAmount;
            if ($newBalance < 0) {
                throw new \RuntimeException('Saldo insuficiente');
            }
            $update = $pdo->prepare('UPDATE wallets SET balance = :balance, updated_at = NOW() WHERE wallet_id = :wallet_id');
            $update->execute(['balance' => $newBalance, 'wallet_id' => $wallet['wallet_id']]);

            $txn = $pdo->prepare('INSERT INTO transactions (wallet_id, amount, type, status, reference, external_provider, metadata, created_at) VALUES (:wallet_id, :amount, :type, :status, :reference, :provider, :metadata, NOW())');
            $txn->execute([
                'wallet_id' => $wallet['wallet_id'],
                'amount' => abs($signedAmount),
                'type' => $type,
                'status' => 'completed',
                'reference' => $reference,
                'provider' => $provider,
                'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
            ]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function lockWallet(PDO $pdo, int $userId): array
    {
        $stmt = $pdo->prepare('SELECT wallet_id, balance FROM wallets WHERE user_id = :user_id FOR UPDATE');
        $stmt->execute(['user_id' => $userId]);
        $wallet = $stmt->fetch();
        if (!$wallet) {
            $ins = $pdo->prepare('INSERT INTO wallets (user_id, balance, currency, updated_at) VALUES (:user_id, 0, :currency, NOW())');
            $ins->execute(['user_id' => $userId, 'currency' => 'MZN']);
            return $this->lockWallet($pdo, $userId);
        }
        return $wallet;
    }
}
