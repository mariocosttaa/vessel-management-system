<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;

class AuditLogService
{
    /**
     * Log a create action.
     *
     * @param Model $model The model that was created
     * @param string|null $modelName The display name of the model (e.g., "Transaction", "Crew Position")
     * @param string|null $identifier The identifier of the model (e.g., transaction number, name)
     * @param int|null $vesselId The vessel ID (for multi-tenancy)
     * @return AuditLog
     */
    public static function logCreate(Model $model, ?string $modelName = null, ?string $identifier = null, ?int $vesselId = null): AuditLog
    {
        $modelName = $modelName ?? self::getModelDisplayName($model);
        $identifier = $identifier ?? self::getModelIdentifier($model);
        $user = Auth::user();
        $userName = $user ? $user->name : 'System';

        // Always store message in English - will be translated when displayed
        $identifierText = $identifier ? " '{$identifier}'" : '';
        $message = "{$userName} created {$modelName}{$identifierText}";

        return self::createLog([
            'user_id' => $user?->id,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'action' => 'create',
            'message' => $message,
            'vessel_id' => $vesselId ?? self::getVesselIdFromModel($model),
            'ip_address' => self::getClientIp(),
            'user_agent' => self::getUserAgent(),
        ]);
    }

    /**
     * Log an update action.
     *
     * @param Model $model The model that was updated
     * @param array $changedFields Array of changed fields with old and new values
     * @param string|null $modelName The display name of the model
     * @param string|null $identifier The identifier of the model
     * @param int|null $vesselId The vessel ID (for multi-tenancy)
     * @return AuditLog
     */
    public static function logUpdate(
        Model $model,
        array $changedFields = [],
        ?string $modelName = null,
        ?string $identifier = null,
        ?int $vesselId = null
    ): AuditLog {
        $modelName = $modelName ?? self::getModelDisplayName($model);
        $identifier = $identifier ?? self::getModelIdentifier($model);
        $user = Auth::user();
        $userName = $user ? $user->name : 'System';

        // Always store message in English - will be translated when displayed
        // Build change messages
        $changes = [];
        foreach ($changedFields as $field => $values) {
            $oldValue = $values['old'] ?? null;
            $newValue = $values['new'] ?? null;
            $fieldName = self::formatFieldName($field);
            $changes[] = "{$fieldName} from '{$oldValue}' to '{$newValue}'";
        }

        if (empty($changes)) {
            $message = "{$userName} updated in {$modelName}" . ($identifier ? " '{$identifier}'" : '');
        } else {
            $changesText = implode(', ', $changes);
            $identifierText = $identifier ? " '{$identifier}'" : '';
            $message = "{$userName} changed {$changesText} in {$modelName}{$identifierText}";
        }

        return self::createLog([
            'user_id' => $user?->id,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'action' => 'update',
            'message' => $message,
            'vessel_id' => $vesselId ?? self::getVesselIdFromModel($model),
            'ip_address' => self::getClientIp(),
            'user_agent' => self::getUserAgent(),
        ]);
    }

    /**
     * Log a delete action.
     *
     * @param Model $model The model that was deleted (should have the data before deletion)
     * @param string|null $modelName The display name of the model
     * @param string|null $identifier The identifier of the model
     * @param int|null $vesselId The vessel ID (for multi-tenancy)
     * @return AuditLog
     */
    public static function logDelete(Model $model, ?string $modelName = null, ?string $identifier = null, ?int $vesselId = null): AuditLog
    {
        $modelName = $modelName ?? self::getModelDisplayName($model);
        $identifier = $identifier ?? self::getModelIdentifier($model);
        $user = Auth::user();
        $userName = $user ? $user->name : 'System';

        // Always store message in English - will be translated when displayed
        $identifierText = $identifier ? " '{$identifier}'" : '';
        $message = "{$userName} deleted {$modelName}{$identifierText}";

        return self::createLog([
            'user_id' => $user?->id,
            'model_type' => get_class($model),
            'model_id' => $model->id ?? null,
            'action' => 'delete',
            'message' => $message,
            'vessel_id' => $vesselId ?? self::getVesselIdFromModel($model),
            'ip_address' => self::getClientIp(),
            'user_agent' => self::getUserAgent(),
        ]);
    }

    /**
     * Create an audit log entry.
     */
    protected static function createLog(array $data): AuditLog
    {
        return AuditLog::create($data);
    }

    /**
     * Get the display name for a model.
     */
    protected static function getModelDisplayName(Model $model): string
    {
        $className = class_basename($model);

        // Convert CamelCase to words (e.g., "CrewPosition" -> "Crew Position")
        $name = preg_replace('/(?<!^)([A-Z])/', ' $1', $className);

        return $name;
    }

    /**
     * Get the identifier for a model (name, transaction_number, etc.).
     */
    protected static function getModelIdentifier(Model $model): ?string
    {
        // Try common identifier fields
        $identifierFields = ['name', 'transaction_number', 'marea_number', 'registration_number', 'email', 'company_name'];

        foreach ($identifierFields as $field) {
            if (isset($model->$field)) {
                return (string) $model->$field;
            }
        }

        // Fallback to ID
        return $model->id ? (string) $model->id : null;
    }

    /**
     * Get vessel ID from model if it has a vessel_id field.
     */
    protected static function getVesselIdFromModel(Model $model): ?int
    {
        if (isset($model->vessel_id)) {
            return (int) $model->vessel_id;
        }

        return null;
    }

    /**
     * Format field name for display (e.g., "transaction_date" -> "Transaction Date").
     */
    protected static function formatFieldName(string $field): string
    {
        // Replace underscores with spaces and capitalize words
        return ucwords(str_replace('_', ' ', $field));
    }

    /**
     * Extract changed fields from a model's dirty attributes.
     *
     * @param Model $model The model with dirty attributes
     * @param Model|null $originalModel The original model state (optional, if available)
     * @return array Array of changed fields with old and new values
     */
    public static function getChangedFields(Model $model, ?Model $originalModel = null): array
    {
        $changedFields = [];
        $dirtyAttributes = $model->getDirty();

        foreach ($dirtyAttributes as $field => $newValue) {
            $oldValue = $originalModel?->getOriginal($field) ?? $model->getOriginal($field);

            // Format values for display
            $oldValue = self::formatValue($oldValue);
            $newValue = self::formatValue($newValue);

            $changedFields[$field] = [
                'old' => $oldValue,
                'new' => $newValue,
            ];
        }

        return $changedFields;
    }

    /**
     * Format a value for display in audit logs.
     */
    protected static function formatValue($value): string
    {
        // Always format in English - will be translated when displayed
        if (is_null($value)) {
            return '(empty)';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }

    /**
     * Get client IP address from request.
     */
    protected static function getClientIp(): ?string
    {
        $request = request();
        if (!$request) {
            return null;
        }

        // Try to get IP from various sources
        $ip = $request->ip();
        if ($ip) {
            return $ip;
        }

        // Fallback to server variables
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }

        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /**
     * Get user agent from request.
     */
    protected static function getUserAgent(): ?string
    {
        $request = request();
        if (!$request) {
            return null;
        }

        return $request->userAgent() ?? $_SERVER['HTTP_USER_AGENT'] ?? null;
    }
}

