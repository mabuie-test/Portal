<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Security\SeedCrypto;
use App\Services\ProvablyFairService;
use PHPUnit\Framework\TestCase;

final class ProvablyFairServiceTest extends TestCase
{
    public function testDerivationIsDeterministic(): void
    {
        $svc = new ProvablyFairService(new SeedCrypto());
        $a = $svc->deriveCrashMultiplier('abc123', 'client-x', 42, 0.01);
        $b = $svc->deriveCrashMultiplier('abc123', 'client-x', 42, 0.01);
        self::assertSame($a['multiplier'], $b['multiplier']);
        self::assertSame($a['hmac'], $b['hmac']);
    }
}
