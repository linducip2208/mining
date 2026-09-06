<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

final class DocumentVerificationService
{
    public static function token(string $type, string $reference): string
    {
        return rtrim(strtr(base64_encode(Crypt::encryptString(json_encode(['type' => $type, 'reference' => $reference]))), '+/', '-_'), '=');
    }

    public static function decode(string $token): ?array
    {
        try {
            $token .= str_repeat('=', (4 - strlen($token) % 4) % 4);

            return json_decode(Crypt::decryptString(base64_decode(strtr($token, '-_', '+/'))), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }
    }
}
