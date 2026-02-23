<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Env;
use App\Repositories\GameRepository;
use App\Repositories\RoundRepository;
use App\Security\SeedCrypto;
use App\Services\CrashEngine;
use App\Services\ProvablyFairService;
use App\Services\RoundService;

Env::load(dirname(__DIR__));

$games = new GameRepository();
$roundRepo = new RoundRepository();
$fair = new ProvablyFairService(new SeedCrypto());
$engine = new CrashEngine($fair);
$roundService = new RoundService($engine, $fair, $roundRepo);

while (true) {
    foreach ($games->listActive() as $game) {
        $round = $roundService->startRound((string) $game['shortcode'], (int) $game['id'], random_int(1, 1000000));
        echo sprintf("[%s] round=%d game=%s crash=%.2fx hash=%s\n", date('c'), $round['round_id'], $game['shortcode'], $round['crash_multiplier'], $round['server_seed_hash']);
        usleep(400000);
        $roundService->finishRound((int) $round['round_id']);
    }
    sleep(1);
}
