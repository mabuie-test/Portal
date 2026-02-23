<?php
return [
    'aviator' => ['name' => 'Aviator-clone', 'shortcode' => 'aviator', 'min_bet' => 5, 'max_bet' => 1000, 'house_edge' => 0.01, 'volatility' => 'medium', 'avg_round_seconds' => 12, 'seed_rotation_interval' => 100],
    'rocket' => ['name' => 'Rocket', 'shortcode' => 'rocket', 'min_bet' => 5, 'max_bet' => 1200, 'house_edge' => 0.012, 'volatility' => 'high', 'avg_round_seconds' => 8, 'seed_rotation_interval' => 100],
    'balloon' => ['name' => 'Balloon', 'shortcode' => 'balloon', 'min_bet' => 5, 'max_bet' => 800, 'house_edge' => 0.01, 'volatility' => 'low', 'avg_round_seconds' => 14, 'seed_rotation_interval' => 120],
    'race' => ['name' => 'Race', 'shortcode' => 'race', 'min_bet' => 5, 'max_bet' => 900, 'house_edge' => 0.01, 'volatility' => 'medium', 'avg_round_seconds' => 11, 'seed_rotation_interval' => 100],
    'streak' => ['name' => 'Streak', 'shortcode' => 'streak', 'min_bet' => 5, 'max_bet' => 700, 'house_edge' => 0.013, 'volatility' => 'high', 'avg_round_seconds' => 7, 'seed_rotation_interval' => 80],
    'steady' => ['name' => 'Steady', 'shortcode' => 'steady', 'min_bet' => 5, 'max_bet' => 650, 'house_edge' => 0.009, 'volatility' => 'low', 'avg_round_seconds' => 16, 'seed_rotation_interval' => 150],
    'turbo' => ['name' => 'Turbo', 'shortcode' => 'turbo', 'min_bet' => 5, 'max_bet' => 1500, 'house_edge' => 0.014, 'volatility' => 'high', 'avg_round_seconds' => 6, 'seed_rotation_interval' => 80],
    'jackpot' => ['name' => 'Progressive Jackpot', 'shortcode' => 'jackpot', 'min_bet' => 5, 'max_bet' => 2000, 'house_edge' => 0.02, 'volatility' => 'high', 'avg_round_seconds' => 12, 'seed_rotation_interval' => 100, 'jackpot_threshold' => 50],
    'tournament' => ['name' => 'Tournament', 'shortcode' => 'tournament', 'min_bet' => 5, 'max_bet' => 1000, 'house_edge' => 0.012, 'volatility' => 'medium', 'avg_round_seconds' => 10, 'seed_rotation_interval' => 100],
    'demo' => ['name' => 'Demo', 'shortcode' => 'demo', 'min_bet' => 5, 'max_bet' => 10000, 'house_edge' => 0.0, 'volatility' => 'medium', 'avg_round_seconds' => 10, 'seed_rotation_interval' => 100, 'fake_credits' => true],
    'wheel' => ['name' => 'Wheel of Odds', 'shortcode' => 'wheel', 'min_bet' => 5, 'max_bet' => 3000, 'house_edge' => 0.02, 'volatility' => 'high', 'avg_round_seconds' => 8, 'seed_rotation_interval' => 100],
    'dice' => ['name' => 'Dice Duel', 'shortcode' => 'dice', 'min_bet' => 5, 'max_bet' => 2500, 'house_edge' => 0.02, 'volatility' => 'medium', 'avg_round_seconds' => 7, 'seed_rotation_interval' => 100],
];
