<?php

namespace App\Services;

use App\Actions\AuditLogAction;
use App\Models\Marea;
use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselSetting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MareaService
{
    /**
     * Create a new marea.
     *
     * @param User $user The user creating the marea
     * @param int $vesselId The ID of the vessel
     * @param array $data Validated request data
     * @return Marea
     * @throws \Exception
     */
    public function createMarea(User $user, int $vesselId, array $data): Marea
    {
        try {
            // Check for unique constraint manually to provide better error messages
            // (Although controller validation should catch most, race conditions or soft deletes need handling)
            $this->checkMareaNumberAvailability($vesselId, $data['marea_number']);

            $vessel = Vessel::find($vesselId);
            $vesselSetting = VesselSetting::getForVessel($vesselId);
            $defaultCurrency = $vesselSetting->currency_code ?? $vessel->currency_code ?? 'EUR';

            $marea = Marea::create([
                'vessel_id'                => $vesselId,
                'marea_number'             => $data['marea_number'],
                'estimated_departure_date' => $data['estimated_departure_date'] ?? null,
                'estimated_return_date'    => $data['estimated_return_date'] ?? null,
                'distribution_profile_id'  => null,  // Not set during creation
                'use_calculation'          => false, // Default to false
                'currency'                 => $defaultCurrency,
                'house_of_zeros'           => 2, // Default
                'status'                   => 'preparing',
                'created_by'               => $user->id,
            ]);

            // Log the create action
            AuditLogAction::logCreate(
                $marea,
                'Marea',
                $marea->marea_number,
                $vesselId
            );

            return $marea;
        } catch (QueryException $e) {
            $this->handleQueryException($e, $vesselId, $data['marea_number']);
            throw $e; // Should be unreachable if handleQueryException throws ValidationException
        }
    }

    /**
     * Update an existing marea.
     *
     * @param Marea $marea
     * @param User $user
     * @param array $data
     * @return Marea
     */
    public function updateMarea(Marea $marea, User $user, array $data): Marea
    {
        // Store original state for change detection
        $originalMarea = $marea->replicate();

        // If marea number is changing, check availability
        if (isset($data['marea_number']) && $data['marea_number'] !== $marea->marea_number) {
            $this->checkMareaNumberAvailability($marea->vessel_id, $data['marea_number'], $marea->id);
        }

        $marea->update([
            'marea_number'             => $data['marea_number'] ?? $marea->marea_number,
            'name'                     => $data['name'] ?? $marea->name,
            'description'              => $data['description'] ?? $marea->description,
            'estimated_departure_date' => $data['estimated_departure_date'] ?? $marea->estimated_departure_date,
            'estimated_return_date'    => $data['estimated_return_date'] ?? $marea->estimated_return_date,
            'actual_departure_date'    => $data['actual_departure_date'] ?? $marea->actual_departure_date,
            'actual_return_date'       => $data['actual_return_date'] ?? $marea->actual_return_date,
            'distribution_profile_id'  => array_key_exists('distribution_profile_id', $data) ? $data['distribution_profile_id'] : $marea->distribution_profile_id,
            'use_calculation'          => $data['use_calculation'] ?? $marea->use_calculation,
            'status'                   => $data['status'] ?? $marea->status,
        ]);

        // Get changed fields and log the update action
        $changedFields = AuditLogAction::getChangedFields($marea, $originalMarea);
        AuditLogAction::logUpdate(
            $marea,
            $changedFields,
            'Marea',
            $marea->marea_number,
            $marea->vessel_id
        );

        return $marea;
    }

    /**
     * Check if a marea number is available, handling soft deletes and duplicates.
     *
     * @param int $vesselId
     * @param string $mareaNumber
     * @param int|null $ignoreId ID to ignore (for updates)
     * @throws ValidationException
     */
    protected function checkMareaNumberAvailability(int $vesselId, string $mareaNumber, ?int $ignoreId = null): void
    {
        $query = Marea::withTrashed()->where('marea_number', $mareaNumber);
        
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $existingMarea = $query->first();

        if ($existingMarea) {
            if ($existingMarea->trashed()) {
                $nextMareaDetails = Marea::getNextMareaNumber($vesselId, true);
                throw ValidationException::withMessages([
                    'marea_number' => __('notifications.This marea number is already in use (even if deleted). Suggested number: :number', [
                        'number' => $nextMareaDetails['suggested_number'],
                    ]),
                ]);
            } else {
                throw ValidationException::withMessages([
                    'marea_number' => __('notifications.This marea number is already in use. Please use a different number.'),
                ]);
            }
        }

        // Check for numeric conflicts with soft-deleted records (custom logic from controller)
        // Extract numeric part
        preg_match('/(\d+)/', $mareaNumber, $matches);
        $inputNumericPart = $matches[1] ?? null;

        if ($inputNumericPart) {
            $softDeletedWithSameNumber = Marea::onlyTrashed()
                ->where('vessel_id', $vesselId)
                ->get()
                ->filter(function ($marea) use ($inputNumericPart) {
                    preg_match('/(\d+)/', $marea->marea_number, $matches);
                    $numericPart = $matches[1] ?? null;
                    return $numericPart === $inputNumericPart;
                })
                ->first();

            if ($softDeletedWithSameNumber) {
                $nextMareaDetails = Marea::getNextMareaNumber($vesselId, true);
                throw ValidationException::withMessages([
                    'marea_number' => __('notifications.A soft-deleted marea with this number exists. Suggested number: :number', [
                        'number' => $nextMareaDetails['suggested_number'],
                    ]),
                ]);
            }
        }
    }

    /**
     * Handle QueryExceptions during creation/update.
     *
     * @param QueryException $e
     * @param int $vesselId
     * @param string $mareaNumber
     * @throws ValidationException
     */
    protected function handleQueryException(QueryException $e, int $vesselId, string $mareaNumber): void
    {
        $errorMessage = $e->getMessage();
        if (str_contains($errorMessage, 'UNIQUE constraint failed') ||
            str_contains($errorMessage, 'Integrity constraint violation') ||
            $e->getCode() === '23000' ||
            ($e->errorInfo[0] ?? '') === '23000') {
            
            $nextMareaNumber = Marea::getNextMareaNumber($vesselId);
            
            Log::warning('Marea creation failed - unique constraint violation', [
                'marea_number' => $mareaNumber,
                'vessel_id'    => $vesselId,
                'error'        => $errorMessage,
            ]);

            throw ValidationException::withMessages([
                'marea_number' => __('notifications.This marea number is already in use. Please use the auto-generated number: :number', [
                    'number' => $nextMareaNumber,
                ]),
            ]);
        }
    }
}
