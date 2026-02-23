<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Security\Password;
use PHPUnit\Framework\TestCase;

final class PasswordSecurityTest extends TestCase
{
    public function testArgonHashAndVerify(): void
    {
        $hash = Password::hash('secret!123');
        self::assertTrue(Password::verify('secret!123', $hash));
    }
}
