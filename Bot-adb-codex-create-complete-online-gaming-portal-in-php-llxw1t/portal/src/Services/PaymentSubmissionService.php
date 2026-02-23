<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class PaymentSubmissionService
{
    public function submit(int $userId, string $reference, float $amount, string $phone, ?string $receiptUrl): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO payment_submissions (user_id, submitted_reference, submitted_amount, submitted_phone, attached_receipt_url, status, created_at) VALUES (:user_id,:ref,:amount,:phone,:receipt,:status,NOW())'
        );
        $stmt->execute([
            'user_id' => $userId,
            'ref' => trim($reference),
            'amount' => $amount,
            'phone' => preg_replace('/\s+/', '', $phone),
            'receipt' => $receiptUrl,
            'status' => 'pending',
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function verifyAndCredit(int $submissionId, int $adminId, string $note, WalletService $walletService): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT * FROM payment_submissions WHERE submission_id = :id FOR UPDATE');
        $stmt->execute(['id' => $submissionId]);
        $submission = $stmt->fetch();
        if (!$submission || $submission['status'] !== 'pending') {
            $pdo->rollBack();
            throw new \RuntimeException('Submissão inválida');
        }
        $upd = $pdo->prepare('UPDATE payment_submissions SET status = :status, admin_note = :note, verified_at = NOW() WHERE submission_id = :id');
        $upd->execute(['status' => 'verified', 'note' => $note, 'id' => $submissionId]);
        $pdo->commit();

        $walletService->credit((int) $submission['user_id'], (float) $submission['submitted_amount'], (string) $submission['submitted_reference'], 'manual_transfer', ['submission_id' => $submissionId]);
        AuditLogService::record($adminId, 'payment_submission.verified', 'payment_submission', $submissionId, $note, $_SERVER['REMOTE_ADDR'] ?? 'cli', ['amount' => $submission['submitted_amount']]);
    }

    public function reject(int $submissionId, int $adminId, string $note): void
    {
        $stmt = Database::connection()->prepare('UPDATE payment_submissions SET status = :status, admin_note = :note, verified_at = NOW() WHERE submission_id = :id');
        $stmt->execute(['status' => 'rejected', 'note' => $note, 'id' => $submissionId]);
        AuditLogService::record($adminId, 'payment_submission.rejected', 'payment_submission', $submissionId, $note, $_SERVER['REMOTE_ADDR'] ?? 'cli', []);
    }
}
