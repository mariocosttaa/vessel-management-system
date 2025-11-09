<?php

namespace App\Actions\General;

use Illuminate\Support\Facades\Crypt;

class EasyHashAction
{
    /**
     * Encode an ID with a type identifier.
     *
     * @param int|string $id
     * @param string $type
     * @return string
     */
    public static function encode($id, string $type): string
    {
        $payload = [
            'id' => $id,
            'type' => $type,
            'timestamp' => now()->timestamp,
        ];

        return base64_encode(Crypt::encrypt(json_encode($payload)));
    }

    /**
     * Decode a hashed ID.
     *
     * @param string $hashed
     * @param string $type
     * @return int|string|null
     */
    public static function decode(string $hashed, string $type)
    {
        try {
            $decrypted = Crypt::decrypt(base64_decode($hashed));
            $payload = json_decode($decrypted, true);

            if (!isset($payload['id']) || !isset($payload['type']) || $payload['type'] !== $type) {
                return null;
            }

            return $payload['id'];
        } catch (\Exception $e) {
            return null;
        }
    }
}

