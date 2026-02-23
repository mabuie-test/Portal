<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Response;
use App\Repositories\BetRepository;
use App\Repositories\GameRepository;
use App\Repositories\RoundRepository;
use App\Security\Csrf;
use App\Security\Password;
use App\Security\SeedCrypto;
use App\Services\BetService;
use App\Services\CoinFlipService;
use App\Services\CrashEngine;
use App\Services\PaymentSubmissionService;
use App\Services\ProvablyFairService;
use App\Services\RoundService;
use App\Services\WalletService;
use App\Services\WheelService;
use App\Services\DiceDuelService;
use App\Services\ProfileService;
use App\Services\AuthService;

final class ApiController
{

    private function requireAdmin(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $uid = (int) ($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            throw new \RuntimeException('Não autenticado');
        }
        $stmt = Database::connection()->prepare('SELECT role_id FROM users WHERE id=:id LIMIT 1');
        $stmt->execute(['id' => $uid]);
        $user = $stmt->fetch();
        if (!$user || (int) $user['role_id'] !== 3) {
            throw new \RuntimeException('Acesso admin requerido');
        }
    }


    public function registerAccount(): void
    {
        $data = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $auth = new AuthService();
        try {
            $id = $auth->register(
                (string) ($data['full_name'] ?? ''),
                (string) ($data['email'] ?? ''),
                (string) ($data['phone'] ?? ''),
                (string) ($data['birth_date'] ?? ''),
                (string) ($data['password'] ?? '')
            );
            Response::json(['message' => 'Conta criada', 'user_id' => $id, 'user_code' => str_pad((string) $id, 5, '0', STR_PAD_LEFT)], 201);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function loginAccount(): void
    {
        $data = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $auth = new AuthService();
        try {
            $user = $auth->login((string) ($data['email'] ?? ''), (string) ($data['password'] ?? ''));
            Response::json(['message' => 'Login efetuado', 'data' => $user]);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 401);
        }
    }

    public function requestPasswordReset(): void
    {
        $data = json_decode((string) file_get_contents('php://input'), true) ?: [];
        (new AuthService())->requestPasswordReset((string) ($data['email'] ?? ''));
        Response::json(['message' => 'Se o email existir, foi enviado um link de recuperação']);
    }

    public function resetPassword(): void
    {
        $data = json_decode((string) file_get_contents('php://input'), true) ?: [];
        try {
            (new AuthService())->resetPassword((string) ($data['email'] ?? ''), (string) ($data['token'] ?? ''), (string) ($data['new_password'] ?? ''));
            Response::json(['message' => 'Senha atualizada']);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function profile(): void
    {
        $userId = (int) ($_GET['user_id'] ?? ($_SESSION['user_id'] ?? 0));
        try {
            $data = (new ProfileService())->getProfile($userId);
            Response::json(['data' => $data]);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 404);
        }
    }

    public function updateProfilePreferences(): void
    {
        $payload = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $userId = (int) ($payload['user_id'] ?? ($_SESSION['user_id'] ?? 0));
        (new ProfileService())->updatePreferences($userId, (array) ($payload['preferences'] ?? []));
        Response::json(['message' => 'Preferências atualizadas']);
    }

    public function betHistory(): void
    {
        $userId = (int) ($_GET['user_id'] ?? ($_SESSION['user_id'] ?? 0));
        $data = (new ProfileService())->betHistory($userId);
        Response::json(['data' => $data]);
    }

    public function withdrawalHistory(): void
    {
        $userId = (int) ($_GET['user_id'] ?? ($_SESSION['user_id'] ?? 0));
        $data = (new ProfileService())->withdrawals($userId);
        Response::json(['data' => $data]);
    }

    public function csrf(): void
    {
        Response::json(['csrf_token' => Csrf::token()]);
    }

    public function register(): void
    {
        $data = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $hash = Password::hash((string) ($data['password'] ?? ''));
        Response::json(['message' => 'Registo criado', 'password_hash_example' => $hash], 201);
    }

    public function listGames(): void
    {
        $repo = new GameRepository();
        Response::json(['data' => $repo->listActive()]);
    }

    public function submitManualDeposit(): void
    {
        $data = json_decode((string) file_get_contents('php://input'), true) ?: [];
        if (!Csrf::validate($data['csrf_token'] ?? null)) {
            Response::json(['error' => 'CSRF inválido'], 422);
            return;
        }

        $service = new PaymentSubmissionService();
        $id = $service->submit((int) ($data['user_id'] ?? 0), (string) ($data['reference'] ?? ''), (float) ($data['amount'] ?? 0), (string) ($data['phone'] ?? ''), $data['receipt_url'] ?? null);
        Response::json(['message' => 'Submissão pendente', 'submission_id' => $id], 201);
    }

    public function fairnessProof(): void
    {
        $seed = $_GET['server_seed'] ?? 'seed';
        $client = $_GET['client_seed'] ?? 'client';
        $nonce = (int) ($_GET['nonce'] ?? 1);
        $svc = new ProvablyFairService(new SeedCrypto());
        Response::json($svc->deriveCrashMultiplier((string) $seed, (string) $client, $nonce));
    }

    public function roundPreview(): void
    {
        $game = (string) ($_GET['game'] ?? 'aviator');
        $engine = new CrashEngine(new ProvablyFairService(new SeedCrypto()));
        Response::json($engine->startRound($game));
    }

    public function createRound(): void
    {
        $data = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $game = (string) ($data['game'] ?? 'aviator');
        $gameRepo = new GameRepository();
        $gameRow = $gameRepo->findByShortcode($game);
        if (!$gameRow) {
            Response::json(['error' => 'Jogo não encontrado'], 404);
            return;
        }

        $fair = new ProvablyFairService(new SeedCrypto());
        $engine = new CrashEngine($fair);
        $roundService = new RoundService($engine, $fair, new RoundRepository());
        $round = $roundService->startRound($game, (int) $gameRow['id'], (int) ($data['nonce'] ?? random_int(1, 1000000)), $data['client_seed'] ?? null);
        Response::json(['data' => $round], 201);
    }

    public function placeBet(): void
    {
        $data = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $service = new BetService(new WalletService(), new BetRepository());
        $betId = $service->placeBet((int) $data['round_id'], (int) $data['user_id'], (float) $data['amount'], isset($data['auto_cashout']) ? (float) $data['auto_cashout'] : null);
        Response::json(['message' => 'Aposta criada', 'bet_id' => $betId], 201);
    }

    public function cashout(): void
    {
        $data = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $service = new BetService(new WalletService(), new BetRepository());
        $result = $service->cashout((int) $data['bet_id'], (float) $data['multiplier']);
        Response::json(['message' => 'Cashout efetuado', 'data' => $result]);
    }

    public function listRounds(): void
    {
        $gameId = (int) ($_GET['game_id'] ?? 0);
        if ($gameId <= 0) {
            Response::json(['error' => 'game_id é obrigatório'], 422);
            return;
        }
        $repo = new RoundRepository();
        Response::json(['data' => $repo->latestByGame($gameId)]);
    }



    public function walletBalance(): void
    {
        $userId = (int) ($_GET['user_id'] ?? 0);
        if ($userId <= 0) {
            Response::json(['error' => 'user_id é obrigatório'], 422);
            return;
        }

        $stmt = Database::connection()->prepare('SELECT balance, currency FROM wallets WHERE user_id=:user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $wallet = $stmt->fetch();

        if (!$wallet) {
            Response::json(['data' => ['balance' => 0, 'currency' => 'MZN']]);
            return;
        }

        Response::json(['data' => ['balance' => (float) $wallet['balance'], 'currency' => $wallet['currency']]]);
    }



    public function playDiceDuel(): void
    {
        $data = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $svc = new DiceDuelService(new ProvablyFairService(new SeedCrypto()), new WalletService());
        try {
            $result = $svc->play(
                (int) ($data['user_id'] ?? 0),
                (float) ($data['amount'] ?? 0),
                (string) ($data['bet_type'] ?? ''),
                (string) ($data['selection'] ?? ''),
                $data['client_seed'] ?? null
            );
            Response::json(['message' => 'Duelo de dados concluído', 'data' => $result], 201);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function playWheel(): void
    {
        $data = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $service = new WheelService(new ProvablyFairService(new SeedCrypto()), new WalletService());
        try {
            $result = $service->play((int) ($data['user_id'] ?? 0), (float) ($data['amount'] ?? 0), $data['client_seed'] ?? null);
            Response::json(['message' => 'Wheel concluído', 'data' => $result], 201);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function adminUsers(): void
    {
        try { $this->requireAdmin(); } catch (\Throwable $e) { Response::json(['error'=>$e->getMessage()],403); return; }
        $stmt = Database::connection()->query('SELECT id, full_name, email, phone, status, role_id, created_at FROM users ORDER BY id DESC LIMIT 300');
        Response::json(['data' => $stmt->fetchAll()]);
    }

    public function adminPendingSubmissions(): void
    {
        try { $this->requireAdmin(); } catch (\Throwable $e) { Response::json(['error'=>$e->getMessage()],403); return; }
        $stmt = Database::connection()->query("SELECT submission_id, user_id, submitted_reference, submitted_amount, submitted_phone, status, created_at FROM payment_submissions WHERE status='pending' ORDER BY created_at DESC LIMIT 300");
        Response::json(['data' => $stmt->fetchAll()]);
    }

    public function adminVerifySubmission(): void
    {
        try { $this->requireAdmin(); } catch (\Throwable $e) { Response::json(['error'=>$e->getMessage()],403); return; }
        $data = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $svc = new PaymentSubmissionService();
        try {
            $svc->verifyAndCredit((int) $data['submission_id'], (int) ($_SESSION['user_id'] ?? 0), (string) ($data['note'] ?? 'validated'), new WalletService());
            Response::json(['message' => 'Submissão validada e carteira creditada']);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function adminRejectSubmission(): void
    {
        try { $this->requireAdmin(); } catch (\Throwable $e) { Response::json(['error'=>$e->getMessage()],403); return; }
        $data = json_decode((string) file_get_contents('php://input'), true) ?: [];
        (new PaymentSubmissionService())->reject((int) $data['submission_id'], (int) ($_SESSION['user_id'] ?? 0), (string) ($data['note'] ?? 'rejected'));
        Response::json(['message' => 'Submissão rejeitada']);
    }

    public function adminFinancialReport(): void
    {
        try { $this->requireAdmin(); } catch (\Throwable $e) { Response::json(['error'=>$e->getMessage()],403); return; }
        $pdo = Database::connection();
        $deposits = $pdo->query("SELECT COALESCE(SUM(amount),0) total FROM transactions WHERE type='credit' AND external_provider IN ('manual_transfer')")->fetch();
        $payouts = $pdo->query("SELECT COALESCE(SUM(amount),0) total FROM transactions WHERE type='credit' AND external_provider IN ('game','coinflip','wheel')")->fetch();
        $bets = $pdo->query("SELECT COALESCE(SUM(amount),0) total FROM bets")->fetch();
        $wallets = $pdo->query("SELECT COALESCE(SUM(balance),0) total FROM wallets")->fetch();
        Response::json(['data' => [
            'deposits_total' => (float) ($deposits['total'] ?? 0),
            'payouts_total' => (float) ($payouts['total'] ?? 0),
            'bets_volume' => (float) ($bets['total'] ?? 0),
            'wallets_aggregate' => (float) ($wallets['total'] ?? 0),
        ]]);
    }

    public function adminToggleGame(): void
    {
        try { $this->requireAdmin(); } catch (\Throwable $e) { Response::json(['error'=>$e->getMessage()],403); return; }
        $data = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $status = in_array(($data['status'] ?? ''), ['active','inactive','maintenance'], true) ? $data['status'] : 'inactive';
        $stmt = Database::connection()->prepare('UPDATE games SET status=:status WHERE shortcode=:shortcode');
        $stmt->execute(['status' => $status, 'shortcode' => (string) ($data['shortcode'] ?? '')]);
        Response::json(['message' => 'Estado do jogo atualizado']);
    }

    public function playCoinFlip(): void
    {
        $data = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $service = new CoinFlipService(new ProvablyFairService(new SeedCrypto()), new WalletService());

        try {
            $result = $service->play(
                (int) ($data['user_id'] ?? 0),
                (float) ($data['amount'] ?? 0),
                (string) ($data['choice'] ?? ''),
                $data['client_seed'] ?? null
            );
            Response::json(['message' => 'Coin flip concluído', 'data' => $result], 201);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function roundProof(): void
    {
        $roundId = (int) ($_GET['round_id'] ?? 0);
        $stmt = Database::connection()->prepare('SELECT round_id, published_hash, revealed_seed, client_seed, nonce, hmac_result, calculation_steps, created_at FROM provably_proofs WHERE round_id=:id LIMIT 1');
        $stmt->execute(['id' => $roundId]);
        $row = $stmt->fetch();
        if (!$row) {
            Response::json(['error' => 'proof não encontrada'], 404);
            return;
        }
        Response::json(['data' => $row]);
    }
}
