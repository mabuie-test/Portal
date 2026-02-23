<?php

declare(strict_types=1);

namespace App\Security;

final class SeedCrypto
{
    public function encrypt(string $plain, string $masterKey): string
    {
        $key = base64_decode(str_replace('base64:', '', $masterKey), true) ?: $masterKey;
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plain, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $cipher);
    }

    public function decrypt(string $encrypted, string $masterKey): string
    {
        $key = base64_decode(str_replace('base64:', '', $masterKey), true) ?: $masterKey;
        $raw = base64_decode($encrypted, true);
        $iv = substr($raw ?: '', 0, 16);
        $cipher = substr($raw ?: '', 16);
        return (string) openssl_decrypt($cipher, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    }
}
