<?php

declare(strict_types=1);

if ($argc < 2) {
    echo "Uso: php scripts/reconcile_csv.php caminho.csv\n";
    exit(1);
}
$csv = $argv[1];
$handle = fopen($csv, 'r');
$rows = [];
$headers = fgetcsv($handle);
while (($line = fgetcsv($handle)) !== false) {
    $rows[] = array_combine($headers, $line);
}
fclose($handle);

$report = [];
foreach ($rows as $row) {
    $score = 0;
    if (!empty($row['reference'])) $score += 60;
    if (!empty($row['phone'])) $score += 20;
    if (!empty($row['amount'])) $score += 20;
    $report[] = ['row' => $row, 'confidence' => $score . '%', 'suggestion' => 'manual_review'];
}
$out = __DIR__ . '/../storage/reconciliation/report_' . date('Ymd_His') . '.json';
file_put_contents($out, json_encode($report, JSON_PRETTY_PRINT));
echo "Relatório: {$out}\n";
