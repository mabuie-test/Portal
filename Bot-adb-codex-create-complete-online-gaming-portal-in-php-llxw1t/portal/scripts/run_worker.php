<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Jobs\JobQueue;

$queue = new JobQueue(__DIR__ . '/../storage/queue/jobs.json');
echo "Worker iniciado\n";
while (true) {
    $job = $queue->pop();
    if (!$job) {
        sleep(1);
        continue;
    }
    try {
        echo 'Processando job: ' . json_encode($job) . PHP_EOL;
        // placeholder para settlement, reconciliação, etc.
    } catch (Throwable $e) {
        $job['attempts'] = ($job['attempts'] ?? 0) + 1;
        if ($job['attempts'] < 5) {
            sleep($job['attempts'] * 2);
            $queue->push($job);
        }
    }
}
