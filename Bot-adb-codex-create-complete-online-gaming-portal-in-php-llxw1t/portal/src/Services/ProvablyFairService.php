<?php

declare(strict_types=1);

namespace App\Services;

use App\Security\SeedCrypto;

final class ProvablyFairService
{
    public function __construct(private readonly SeedCrypto $seedCrypto)
    {
    }

    public function generateServerSeed(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function hashSeed(string $serverSeed): string
    {
        return hash('sha256', $serverSeed);
    }

    /**
     * @return array{hmac:string,int_value:int,normalized:float,multiplier:float}
     */
    public function deriveCrashMultiplier(string $serverSeed, string $clientSeed, int $nonce, float $houseEdge = 0.01): array
    {
        $message = $clientSeed . ':' . $nonce;
        $hmac = hash_hmac('sha256', $message, $serverSeed);
        $hex = substr($hmac, 0, 13); // 52 bits
        $int = intval(hexdec($hex));
        $e = 2 ** 52;
        $normalized = $int / $e;
        $raw = (1 - $houseEdge) / max(0.000001, (1 - $normalized));
        $multiplier = max(1.00, floor($raw * 100) / 100);

        return [
            'hmac' => $hmac,
            'int_value' => $int,
            'normalized' => $normalized,
            'multiplier' => $multiplier,
        ];
    }

    public function encryptSeed(string $seed, string $masterKey): string
    {
        return $this->seedCrypto->encrypt($seed, $masterKey);
    }

    public function decryptSeed(string $encryptedSeed, string $masterKey): string
    {
        return $this->seedCrypto->decrypt($encryptedSeed, $masterKey);
    }
}
