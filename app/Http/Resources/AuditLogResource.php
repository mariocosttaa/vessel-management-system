<?php

namespace App\Http\Resources;

use App\Traits\HasTranslations;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;

class AuditLogResource extends JsonResource
{
    use HasTranslations;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get current viewer's language preference
        $user = $request->user();
        $locale = $user?->language ?? 'en';
        $originalLocale = App::getLocale();
        App::setLocale($locale);

        // Translate the message based on current viewer's language
        $translatedMessage = $this->translateMessage($this->message, $this->action);

        // Translate model name
        $modelName = $this->getModelDisplayName();
        $translatedModelName = $this->translateModelName($modelName);

        // Translate action
        $translatedAction = $this->translateAction($this->action);

        // Format dates according to locale
        $formattedDate = $this->formatDateForLocale($this->created_at, $locale);
        $humanDate = $this->created_at?->locale($locale)->diffForHumans();

        App::setLocale($originalLocale);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name ?? 'System',
            'user_email' => $this->user?->email ?? null,
            'model_type' => $this->model_type,
            'model_id' => $this->model_id,
            'model_name' => $translatedModelName,
            'page_name' => $translatedModelName,
            'action' => $this->action,
            'action_label' => $translatedAction,
            'message' => $translatedMessage,
            'vessel_id' => $this->vessel_id,
            'vessel_name' => $this->vessel?->name,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'created_at_formatted' => $formattedDate,
            'created_at_human' => $humanDate,
        ];
    }

    /**
     * Get the display name for the model type.
     */
    protected function getModelDisplayName(): string
    {
        $className = class_basename($this->model_type);

        // Convert CamelCase to words (e.g., "CrewPosition" -> "Crew Position")
        return preg_replace('/(?<!^)([A-Z])/', ' $1', $className);
    }

    /**
     * Translate the audit log message based on pattern matching.
     */
    protected function translateMessage(string $message, string $action): string
    {
        $locale = App::getLocale();
        
        // Pattern 1: "UserName created ModelName 'identifier'"
        if (preg_match('/^(.+?)\s+created\s+(.+?)(\s+\'(.+?)\')?$/', $message, $matches)) {
            $userName = $matches[1];
            $modelName = $matches[2];
            $identifier = $matches[4] ?? null;
            $identifierText = $identifier ? " '{$identifier}'" : '';
            
            return trans('notifications.:user created :model:identifier', [
                'user' => $userName,
                'model' => $modelName,
                'identifier' => $identifierText,
            ], $locale);
        }
        
        // Pattern 2: "UserName deleted ModelName 'identifier'"
        if (preg_match('/^(.+?)\s+deleted\s+(.+?)(\s+\'(.+?)\')?$/', $message, $matches)) {
            $userName = $matches[1];
            $modelName = $matches[2];
            $identifier = $matches[4] ?? null;
            $identifierText = $identifier ? " '{$identifier}'" : '';
            
            return trans('notifications.:user deleted :model:identifier', [
                'user' => $userName,
                'model' => $modelName,
                'identifier' => $identifierText,
            ], $locale);
        }
        
        // Pattern 3: "UserName updated in ModelName 'identifier'"
        if (preg_match('/^(.+?)\s+updated\s+in\s+(.+?)(\s+\'(.+?)\')?$/', $message, $matches)) {
            $userName = $matches[1];
            $modelName = $matches[2];
            $identifier = $matches[4] ?? null;
            $identifierText = $identifier ? " '{$identifier}'" : '';
            
            return trans('notifications.:user updated :model:identifier', [
                'user' => $userName,
                'model' => $modelName,
                'identifier' => $identifierText,
            ], $locale);
        }
        
        // Pattern 4: "UserName changed changes in ModelName 'identifier'"
        if (preg_match('/^(.+?)\s+changed\s+(.+?)\s+in\s+(.+?)(\s+\'(.+?)\')?$/', $message, $matches)) {
            $userName = $matches[1];
            $changes = $matches[2];
            $modelName = $matches[3];
            $identifier = $matches[5] ?? null;
            $identifierText = $identifier ? " '{$identifier}'" : '';
            
            // Translate individual change parts
            $translatedChanges = $this->translateChanges($changes, $locale);
            
            return trans('notifications.:user changed :changes in :model:identifier', [
                'user' => $userName,
                'changes' => $translatedChanges,
                'model' => $modelName,
                'identifier' => $identifierText,
            ], $locale);
        }
        
        // Fallback: return original message if pattern doesn't match
        return $message;
    }

    /**
     * Translate change descriptions.
     */
    protected function translateChanges(string $changes, string $locale): string
    {
        // Split by comma and translate each change
        $changeParts = explode(', ', $changes);
        $translatedParts = [];
        
        foreach ($changeParts as $change) {
            if (preg_match('/^(.+?)\s+from\s+\'(.+?)\'\s+to\s+\'(.+?)\'$/', $change, $matches)) {
                $field = $matches[1];
                $oldValue = $matches[2];
                $newValue = $matches[3];
                
                // Translate values
                $oldValue = $this->translateValue($oldValue, $locale);
                $newValue = $this->translateValue($newValue, $locale);
                
                $translatedParts[] = trans('notifications.changed :field from \':old\' to \':new\'', [
                    'field' => $field,
                    'old' => $oldValue,
                    'new' => $newValue,
                ], $locale);
            } else {
                $translatedParts[] = $change;
            }
        }
        
        return implode(', ', $translatedParts);
    }

    /**
     * Translate value (empty, Yes, No).
     */
    protected function translateValue(string $value, string $locale): string
    {
        if ($value === '(empty)') {
            return trans('notifications.(empty)', [], $locale);
        }
        if ($value === 'Yes') {
            return trans('notifications.Yes', [], $locale);
        }
        if ($value === 'No') {
            return trans('notifications.No', [], $locale);
        }
        return $value;
    }

    /**
     * Translate model name.
     */
    protected function translateModelName(string $modelName): string
    {
        // Model names are usually already in English, but we can add translations if needed
        // For now, return as-is since model names are typically proper nouns
        return $modelName;
    }

    /**
     * Translate action label.
     */
    protected function translateAction(string $action): string
    {
        $locale = App::getLocale();
        
        $translations = [
            'en' => ['create' => 'Created', 'update' => 'Updated', 'delete' => 'Deleted'],
            'pt' => ['create' => 'Criado', 'update' => 'Atualizado', 'delete' => 'Excluído'],
            'es' => ['create' => 'Creado', 'update' => 'Actualizado', 'delete' => 'Eliminado'],
            'fr' => ['create' => 'Créé', 'update' => 'Mis à jour', 'delete' => 'Supprimé'],
        ];
        
        return $translations[$locale][$action] ?? ucfirst($action);
    }

    /**
     * Format date according to locale.
     */
    protected function formatDateForLocale($date, string $locale): ?string
    {
        if (!$date) {
            return null;
        }
        
        $formats = [
            'en' => 'M d, Y, h:i A',
            'pt' => 'd/m/Y, H:i',
            'es' => 'd/m/Y, H:i',
            'fr' => 'd/m/Y, H:i',
        ];
        
        $format = $formats[$locale] ?? $formats['en'];
        
        return $date->format($format);
    }
}

