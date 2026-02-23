<?php

declare(strict_types=1);

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

$cycleSeconds = 12.0; // cada round
$crashRangeMin = 1.10;
$crashRangeMax = 8.00;

while (true) {
    $now = microtime(true);
    $position = fmod($now, $cycleSeconds);
    $progress = $position / $cycleSeconds;

    // crash pseudo-aleatório por round com base no timestamp do round
    $roundIndex = (int) floor($now / $cycleSeconds);
    mt_srand($roundIndex);
    $crashPoint = $crashRangeMin + (mt_rand() / mt_getrandmax()) * ($crashRangeMax - $crashRangeMin);

    $multiplier = 1 + (pow(1.08, $progress * 40) - 1) * 0.06;
    $phase = $multiplier >= $crashPoint ? 'crashed' : 'running';
    if ($progress < 0.03) {
        $phase = 'starting';
        $multiplier = 1.00;
    }

    echo "event: tick\n";
    echo 'data: ' . json_encode([
        'ts' => $now,
        'round_index' => $roundIndex,
        'progress' => round($progress, 4),
        'phase' => $phase,
        'multiplier' => round(min($multiplier, $crashPoint), 2),
        'crash_point' => round($crashPoint, 2),
    ]) . "\n\n";

    @ob_flush();
    @flush();
    usleep(120000);
}
