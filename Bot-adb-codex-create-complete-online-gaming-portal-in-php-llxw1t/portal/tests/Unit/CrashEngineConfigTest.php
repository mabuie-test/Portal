<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Security\SeedCrypto;
use App\Services\CrashEngine;
use App\Services\ProvablyFairService;
use PHPUnit\Framework\TestCase;

final class CrashEngineConfigTest extends TestCase
{
    public function testHasTenGamesConfigured(): void
    {
        $engine = new CrashEngine(new ProvablyFairService(new SeedCrypto()));
        $games = require __DIR__ . '/../../config/games.php';
        self::assertCount(10, $games);
        self::assertSame('aviator', $engine->config('aviator')['shortcode']);
    }
}
