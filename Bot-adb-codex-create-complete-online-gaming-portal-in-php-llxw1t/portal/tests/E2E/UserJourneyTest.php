<?php

declare(strict_types=1);

namespace Tests\E2E;

use PHPUnit\Framework\TestCase;

final class UserJourneyTest extends TestCase
{
    public function testRegisterDepositBetCashoutFlowBlueprint(): void
    {
        $flow = ['register','manual_deposit','place_bet','cashout','check_balance'];
        self::assertCount(5, $flow);
    }
}
