<?php

namespace App\Http\Resources;

use App\Actions\General\EasyHashAction;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseResource extends JsonResource
{
    /**
     * Get the model name for hashing (e.g., 'transaction', 'user', 'vessel').
     * This should be overridden in child classes if the model name differs.
     */
    protected function getModelName(): string
    {
        // Get class name without namespace (e.g., 'TransactionResource' -> 'Transaction')
        $className = class_basename(static::class);

        // Remove 'Resource' suffix and convert to lowercase
        $modelName = strtolower(str_replace('Resource', '', $className));

        return $modelName;
    }

    /**
     * Hash an ID using the model name pattern.
     * Pattern: {model-name}-id (e.g., 'transaction-id', 'user-id')
     */
    protected function hashId(string|int|null $id): ?string
    {
        if ($id === null) {
            return null;
        }

        $modelName = $this->getModelName();
        $hashType = "{$modelName}-id";

        return EasyHashAction::encode($id, $hashType);
    }

    /**
     * Hash an ID for a specific model type.
     * Useful when hashing IDs for related models.
     */
    protected function hashIdForModel(string|int|null $id, string $modelName): ?string
    {
        if ($id === null) {
            return null;
        }

        $hashType = "{$modelName}-id";
        return EasyHashAction::encode($id, $hashType);
    }
}

