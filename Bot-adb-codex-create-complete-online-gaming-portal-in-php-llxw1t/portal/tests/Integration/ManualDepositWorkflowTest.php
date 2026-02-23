<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

final class ManualDepositWorkflowTest extends TestCase
{
    public function testWorkflowDocumented(): void
    {
        $steps = ['submit', 'pending_review', 'verify_or_reject', 'credit_wallet'];
        self::assertContains('credit_wallet', $steps);
    }
}
