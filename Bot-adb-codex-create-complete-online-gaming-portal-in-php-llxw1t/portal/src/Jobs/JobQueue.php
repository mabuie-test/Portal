<?php

declare(strict_types=1);

namespace App\Jobs;

final class JobQueue
{
    private string $queueFile;

    public function __construct(string $queueFile)
    {
        $this->queueFile = $queueFile;
        if (!file_exists($queueFile)) {
            file_put_contents($queueFile, json_encode([]));
        }
    }

    public function push(array $job): void
    {
        $all = $this->all();
        $job['attempts'] = $job['attempts'] ?? 0;
        $job['created_at'] = date(DATE_ATOM);
        $all[] = $job;
        file_put_contents($this->queueFile, json_encode($all, JSON_PRETTY_PRINT));
    }

    public function pop(): ?array
    {
        $all = $this->all();
        $job = array_shift($all);
        file_put_contents($this->queueFile, json_encode($all, JSON_PRETTY_PRINT));
        return $job ?: null;
    }

    public function all(): array
    {
        return json_decode((string) file_get_contents($this->queueFile), true) ?: [];
    }
}
