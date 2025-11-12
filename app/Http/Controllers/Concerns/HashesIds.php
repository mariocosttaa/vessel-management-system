<?php

namespace App\Http\Controllers\Concerns;

use App\Actions\General\EasyHashAction;

trait HashesIds
{
    /**
     * Unhash an ID from the frontend using the model name pattern.
     * Pattern: {model-name}-id (e.g., 'transaction-id', 'user-id')
     */
    protected function unhashId(?string $hashedId, string $modelName): ?int
    {
        if (empty($hashedId)) {
            return null;
        }

        $hashType = "{$modelName}-id";
        $decoded = EasyHashAction::decode($hashedId, $hashType);

        return is_numeric($decoded) ? (int) $decoded : null;
    }

    /**
     * Hash an ID for sending to frontend using the model name pattern.
     * Pattern: {model-name}-id (e.g., 'transaction-id', 'user-id')
     */
    protected function hashId(?int $id, string $modelName): ?string
    {
        if ($id === null) {
            return null;
        }

        $hashType = "{$modelName}-id";
        return EasyHashAction::encode($id, $hashType);
    }
}

