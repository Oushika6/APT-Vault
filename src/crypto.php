<?php
declare(strict_types=1);

function vault_key(): string
{
    $encoded = env_config('APP_KEY', '');

    if (!str_starts_with((string) $encoded, 'base64:')) {
        throw new RuntimeException('APP_KEY is missing. Generate one and set it in .env.');
    }

    $key = base64_decode(substr($encoded, 7), true);

    if ($key === false || strlen($key) !== 32) {
        throw new RuntimeException('APP_KEY must be a base64-encoded 32-byte key.');
    }

    return $key;
}

function encrypt_secret(string $plaintext): string
{
    $iv = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', vault_key(), OPENSSL_RAW_DATA, $iv, $tag, '', 16);

    if ($ciphertext === false)
        throw new RuntimeException('Encryption failed.');

    return 'v1:' . base64_encode($iv) . ':' . base64_encode($tag) . ':' . base64_encode($ciphertext);
}

function decrypt_secret(string $payload): string
{
    $parts = explode(':', $payload, 4);

    if (count($parts) !== 4 || $parts[0] !== 'v1')
        throw new RuntimeException('Unsupported encrypted data.');

    [$version, $rawIv, $rawTag, $rawCiphertext] = $parts;
    $iv = base64_decode($rawIv, true);
    $tag = base64_decode($rawTag, true);
    $ciphertext = base64_decode($rawCiphertext, true);

    if ($iv === false || strlen($iv) !== 12 || $tag === false || strlen($tag) !== 16 || $ciphertext === false) {
        throw new RuntimeException('Invalid encrypted data.');
    }

    $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', vault_key(), OPENSSL_RAW_DATA, $iv, $tag);

    if ($plaintext === false)
        throw new RuntimeException('Could not decrypt item. Check APP_KEY.');

    return $plaintext;
}
