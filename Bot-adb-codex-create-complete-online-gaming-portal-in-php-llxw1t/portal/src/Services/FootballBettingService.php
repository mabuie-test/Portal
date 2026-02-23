<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class FootballBettingService
{
    public function listEvents(): array
    {
        $stmt = Database::connection()->query("SELECT event_id, event_code, league, home_team, away_team, starts_at, status, home_score, away_score FROM football_events WHERE status IN ('scheduled','live') ORDER BY starts_at ASC LIMIT 200");
        $events = $stmt->fetchAll();
        foreach ($events as &$event) {
            $oddStmt = Database::connection()->prepare("SELECT odd_id, market_type, selection_key, line_value, odd, status FROM football_odds WHERE event_id=:event_id AND status='open' ORDER BY market_type, selection_key");
            $oddStmt->execute(['event_id' => $event['event_id']]);
            $event['markets'] = $oddStmt->fetchAll();
        }
        return $events;
    }

    public function placeTicket(int $userId, float $stake, array $selections): array
    {
        if ($userId <= 0) {
            throw new \RuntimeException('user_id inválido');
        }
        if ($stake < 5) {
            throw new \RuntimeException('Stake mínima é 5');
        }
        if (count($selections) < 1) {
            throw new \RuntimeException('Selecione pelo menos 1 mercado');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $legs = [];
            $totalOdd = 1.0;
            foreach ($selections as $sel) {
                $eventId = (int) ($sel['event_id'] ?? 0);
                $marketType = (string) ($sel['market_type'] ?? '');
                $selectionKey = (string) ($sel['selection_key'] ?? '');
                $lineValue = array_key_exists('line_value', $sel) ? (float) $sel['line_value'] : null;

                $stmt = $pdo->prepare("SELECT o.odd, e.status FROM football_odds o JOIN football_events e ON e.event_id=o.event_id WHERE o.event_id=:event_id AND o.market_type=:market_type AND o.selection_key=:selection_key AND ((o.line_value IS NULL AND :line_value IS NULL) OR o.line_value=:line_value) AND o.status='open' LIMIT 1");
                $stmt->execute([
                    'event_id' => $eventId,
                    'market_type' => $marketType,
                    'selection_key' => $selectionKey,
                    'line_value' => $lineValue,
                ]);
                $row = $stmt->fetch();
                if (!$row || !in_array((string) $row['status'], ['scheduled', 'live'], true)) {
                    throw new \RuntimeException('Mercado indisponível para uma das seleções');
                }

                $odd = (float) $row['odd'];
                $totalOdd *= $odd;
                $legs[] = [
                    'event_id' => $eventId,
                    'market_type' => $marketType,
                    'selection_key' => $selectionKey,
                    'line_value' => $lineValue,
                    'odd' => $odd,
                ];
            }

            $bonusPct = $this->bonusByLegs(count($legs));
            $potential = round($stake * $totalOdd * (1 + $bonusPct / 100), 2);
            $ticketCode = 'FB' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 12));
            $betType = count($legs) > 1 ? 'multi' : 'single';

            $ins = $pdo->prepare('INSERT INTO football_tickets (user_id, ticket_code, bet_type, stake, total_odd, bonus_pct, potential_payout, status, created_at) VALUES (:user_id,:ticket_code,:bet_type,:stake,:total_odd,:bonus_pct,:potential_payout,:status,NOW())');
            $ins->execute([
                'user_id' => $userId,
                'ticket_code' => $ticketCode,
                'bet_type' => $betType,
                'stake' => $stake,
                'total_odd' => round($totalOdd, 2),
                'bonus_pct' => $bonusPct,
                'potential_payout' => $potential,
                'status' => 'open',
            ]);
            $ticketId = (int) $pdo->lastInsertId();

            $selIns = $pdo->prepare('INSERT INTO football_ticket_selections (ticket_id,event_id,market_type,selection_key,line_value,odd,status,created_at) VALUES (:ticket_id,:event_id,:market_type,:selection_key,:line_value,:odd,:status,NOW())');
            foreach ($legs as $leg) {
                $selIns->execute([
                    'ticket_id' => $ticketId,
                    'event_id' => $leg['event_id'],
                    'market_type' => $leg['market_type'],
                    'selection_key' => $leg['selection_key'],
                    'line_value' => $leg['line_value'],
                    'odd' => $leg['odd'],
                    'status' => 'open',
                ]);
            }

            $pdo->commit();
            (new WalletService())->debit($userId, $stake, 'football-ticket-' . $ticketId, 'football', ['ticket_id' => $ticketId]);

            return [
                'ticket_id' => $ticketId,
                'ticket_code' => $ticketCode,
                'stake' => $stake,
                'total_odd' => round($totalOdd, 2),
                'bonus_pct' => $bonusPct,
                'potential_payout' => $potential,
                'legs' => $legs,
            ];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function listTickets(int $userId): array
    {
        $stmt = Database::connection()->prepare('SELECT ticket_id, ticket_code, bet_type, stake, total_odd, bonus_pct, potential_payout, final_payout, status, created_at, settled_at FROM football_tickets WHERE user_id=:uid ORDER BY ticket_id DESC LIMIT 200');
        $stmt->execute(['uid' => $userId]);
        $tickets = $stmt->fetchAll();
        foreach ($tickets as &$ticket) {
            $sel = Database::connection()->prepare('SELECT selection_id, event_id, market_type, selection_key, line_value, odd, status, result_note FROM football_ticket_selections WHERE ticket_id=:ticket_id ORDER BY selection_id ASC');
            $sel->execute(['ticket_id' => $ticket['ticket_id']]);
            $ticket['selections'] = $sel->fetchAll();
        }
        return $tickets;
    }

    public function adminCreateEvent(array $payload, int $adminId): array
    {
        $code = strtoupper(trim((string) ($payload['event_code'] ?? '')));
        if ($code === '') {
            $code = 'EVT' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        }
        $stmt = Database::connection()->prepare('INSERT INTO football_events (event_code,league,home_team,away_team,starts_at,status,created_by,updated_by,created_at,updated_at) VALUES (:event_code,:league,:home_team,:away_team,:starts_at,:status,:created_by,:updated_by,NOW(),NOW())');
        $stmt->execute([
            'event_code' => $code,
            'league' => (string) ($payload['league'] ?? 'Liga Principal'),
            'home_team' => (string) ($payload['home_team'] ?? ''),
            'away_team' => (string) ($payload['away_team'] ?? ''),
            'starts_at' => (string) ($payload['starts_at'] ?? date('Y-m-d H:i:s', time() + 3600)),
            'status' => 'scheduled',
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);
        $eventId = (int) Database::connection()->lastInsertId();

        $odds = (array) ($payload['odds'] ?? []);
        foreach ($odds as $odd) {
            $this->upsertOdd($eventId, (array) $odd);
        }

        return ['event_id' => $eventId, 'event_code' => $code];
    }

    public function adminDeleteEvent(int $eventId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM football_events WHERE event_id=:id');
        $stmt->execute(['id' => $eventId]);
    }

    public function adminSetEventPostponed(int $eventId, int $adminId): void
    {
        $stmt = Database::connection()->prepare("UPDATE football_events SET status='postponed', updated_by=:admin, updated_at=NOW() WHERE event_id=:id");
        $stmt->execute(['admin' => $adminId, 'id' => $eventId]);

        $updSel = Database::connection()->prepare("UPDATE football_ticket_selections SET status='void', result_note='Evento adiado (void)', settled_at=NOW() WHERE event_id=:event_id AND status='open'");
        $updSel->execute(['event_id' => $eventId]);

        $this->settleAffectedTickets();
    }

    public function adminSetResult(int $eventId, array $result, int $adminId): void
    {
        $home = (int) ($result['home_score'] ?? 0);
        $away = (int) ($result['away_score'] ?? 0);
        $first = trim((string) ($result['first_scorer'] ?? ''));

        $stmt = Database::connection()->prepare("UPDATE football_events SET status='finished', home_score=:home, away_score=:away, first_scorer=:first_scorer, updated_by=:admin, updated_at=NOW() WHERE event_id=:id");
        $stmt->execute(['home' => $home, 'away' => $away, 'first_scorer' => ($first ?: null), 'admin' => $adminId, 'id' => $eventId]);

        $sel = Database::connection()->prepare('SELECT selection_id, market_type, selection_key, line_value FROM football_ticket_selections WHERE event_id=:event_id AND status=\'open\'');
        $sel->execute(['event_id' => $eventId]);
        foreach ($sel->fetchAll() as $row) {
            [$status, $note] = $this->evaluateSelection($row['market_type'], $row['selection_key'], $row['line_value'] !== null ? (float) $row['line_value'] : null, $home, $away, $first);
            $upd = Database::connection()->prepare('UPDATE football_ticket_selections SET status=:status, result_note=:note, settled_at=NOW() WHERE selection_id=:id');
            $upd->execute(['status' => $status, 'note' => $note, 'id' => $row['selection_id']]);
        }

        $this->settleAffectedTickets();
    }

    public function adminUpsertOdds(int $eventId, array $odds): void
    {
        foreach ($odds as $odd) {
            $this->upsertOdd($eventId, (array) $odd);
        }
    }

    private function upsertOdd(int $eventId, array $odd): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO football_odds (event_id,market_type,selection_key,line_value,odd,status,created_at,updated_at) VALUES (:event_id,:market_type,:selection_key,:line_value,:odd,:status,NOW(),NOW()) ON DUPLICATE KEY UPDATE odd=VALUES(odd), status=VALUES(status), updated_at=NOW()');
        $stmt->execute([
            'event_id' => $eventId,
            'market_type' => (string) ($odd['market_type'] ?? '1x2'),
            'selection_key' => (string) ($odd['selection_key'] ?? ''),
            'line_value' => array_key_exists('line_value', $odd) ? (float) $odd['line_value'] : null,
            'odd' => (float) ($odd['odd'] ?? 1.0),
            'status' => (string) ($odd['status'] ?? 'open'),
        ]);
    }

    /** @return array{0:string,1:string} */
    private function evaluateSelection(string $market, string $selection, ?float $line, int $home, int $away, string $firstScorer): array
    {
        $total = $home + $away;
        $diff = $home - $away;

        return match ($market) {
            '1x2' => [
                ($selection === 'home' && $home > $away) || ($selection === 'draw' && $home === $away) || ($selection === 'away' && $away > $home) ? 'won' : 'lost',
                'Mercado 1X2 resolvido',
            ],
            'double_chance' => [
                ($selection === '1X' && $home >= $away) || ($selection === 'X2' && $away >= $home) || ($selection === '12' && $home !== $away) ? 'won' : 'lost',
                'Dupla chance resolvida',
            ],
            'handicap' => [
                (($selection === 'home' ? $diff + (float) ($line ?? 0) : -$diff + (float) ($line ?? 0)) > 0) ? 'won' : 'lost',
                'Handicap resolvido',
            ],
            'total_goals' => [
                (($selection === 'over' && $total > (float) ($line ?? 0)) || ($selection === 'under' && $total < (float) ($line ?? 0))) ? 'won' : 'lost',
                'Total de golos resolvido',
            ],
            'first_scorer' => [
                ($firstScorer !== '' && strcasecmp($selection, $firstScorer) === 0) ? 'won' : 'lost',
                'Primeiro a marcar resolvido',
            ],
            default => ['void', 'Mercado não suportado, anulado'],
        };
    }

    private function settleAffectedTickets(): void
    {
        $openTickets = Database::connection()->query("SELECT ticket_id, user_id, stake, total_odd, bonus_pct FROM football_tickets WHERE status='open'")->fetchAll();
        $wallet = new WalletService();

        foreach ($openTickets as $ticket) {
            $selStmt = Database::connection()->prepare('SELECT status, odd FROM football_ticket_selections WHERE ticket_id=:id');
            $selStmt->execute(['id' => $ticket['ticket_id']]);
            $selections = $selStmt->fetchAll();
            if (!$selections) {
                continue;
            }
            $statuses = array_map(static fn($s) => $s['status'], $selections);
            if (in_array('open', $statuses, true)) {
                continue;
            }
            if (in_array('lost', $statuses, true)) {
                $this->finalizeTicket((int) $ticket['ticket_id'], 'lost', 0);
                continue;
            }

            $voidCount = count(array_filter($statuses, static fn($s) => $s === 'void'));
            $wonOdds = 1.0;
            foreach ($selections as $sel) {
                if ($sel['status'] === 'won') {
                    $wonOdds *= (float) $sel['odd'];
                }
            }

            if ($voidCount === count($selections)) {
                $payout = (float) $ticket['stake'];
                $this->finalizeTicket((int) $ticket['ticket_id'], 'void', $payout);
                $wallet->credit((int) $ticket['user_id'], $payout, 'football-ticket-void-' . $ticket['ticket_id'], 'football', ['ticket_id' => (int) $ticket['ticket_id']]);
                continue;
            }

            $bonusFactor = 1 + ((float) $ticket['bonus_pct'] / 100);
            $payout = round((float) $ticket['stake'] * $wonOdds * $bonusFactor, 2);
            $status = $voidCount > 0 ? 'partial_void' : 'won';
            $this->finalizeTicket((int) $ticket['ticket_id'], $status, $payout);
            $wallet->credit((int) $ticket['user_id'], $payout, 'football-ticket-settle-' . $ticket['ticket_id'], 'football', ['ticket_id' => (int) $ticket['ticket_id'], 'status' => $status]);
        }
    }

    private function finalizeTicket(int $ticketId, string $status, float $payout): void
    {
        $stmt = Database::connection()->prepare('UPDATE football_tickets SET status=:status, final_payout=:payout, settled_at=NOW() WHERE ticket_id=:id');
        $stmt->execute(['status' => $status, 'payout' => $payout > 0 ? $payout : null, 'id' => $ticketId]);
    }

    private function bonusByLegs(int $legs): float
    {
        return match (true) {
            $legs >= 10 => 12.0,
            $legs >= 8 => 8.0,
            $legs >= 6 => 5.0,
            $legs >= 4 => 3.0,
            $legs >= 3 => 2.0,
            default => 0.0,
        };
    }
}
