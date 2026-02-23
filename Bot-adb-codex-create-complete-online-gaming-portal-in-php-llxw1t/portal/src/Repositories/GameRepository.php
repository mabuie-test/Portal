<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class GameRepository
{
    public function listActive(): array
    {
        $stmt = Database::connection()->query("SELECT id, name, shortcode, min_bet, max_bet, house_edge, volatility, status, seed_rotation_interval FROM games WHERE status='active' ORDER BY id");
        return $stmt->fetchAll();
    }

    public function findByShortcode(string $shortcode): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM games WHERE shortcode=:shortcode LIMIT 1');
        $stmt->execute(['shortcode' => $shortcode]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
