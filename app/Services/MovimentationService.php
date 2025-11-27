<?php

namespace App\Services;

use App\Actions\AuditLogAction;
use App\Actions\EmailNotificationAction;
use App\Actions\MoneyAction;
use App\Actions\Tenant\TenantFileAction;
use App\Models\Currency;
use App\Models\Movimentation;
use App\Models\MovimentationCategory;
use App\Models\MovimentationFile;
use App\Models\User;
use App\Models\VatProfile;
use App\Models\Vessel;
use App\Models\VesselSetting;
use Illuminate\Support\Facades\Log;

class MovimentationService
{
    /**
     * Create a new transaction.
     *
     * @param User $user The user creating the transaction
     * @param int $vesselId The ID of the vessel
     * @param array $data Validated request data
     * @param array|null $files Uploaded files
     * @return Movimentation
     * @throws \Exception
     */
    public function createTransaction(User $user, int $vesselId, array $data, $files = null): Movimentation
    {
        // Get currency priority: request currency (from form) > vessel_settings > vessel currency_code > EUR
        $vesselSetting = VesselSetting::getForVessel($vesselId);
        $vessel = Vessel::find($vesselId);

        $currency = $data['currency'] ?? $vesselSetting->currency_code ?? $vessel?->currency_code ?? 'EUR';

        // Handle VAT calculation
        $amount = $data['amount'];
        $vatAmount = 0;
        $vatProfileId = $data['vat_profile_id'] ?? null;
        $amountIncludesVat = $data['amount_includes_vat'] ?? false;

        // For income transactions, always get VAT profile from vessel settings or default
        // For expense transactions, vat_profile_id should be null (handled in model boot)
        if (($data['type'] ?? 'expense') === 'income') {
            if (!$vatProfileId) {
                $vatProfileId = $vesselSetting->vat_profile_id
                    ?: (VatProfile::where('is_default', true)->first()?->id);
            }
        } else {
            // Expense transactions don't use VAT
            $vatProfileId = null;
        }

        if ($vatProfileId) {
            $vatProfile = VatProfile::find($vatProfileId);
            if ($vatProfile) {
                $vatRate = (float) $vatProfile->percentage;

                if ($amountIncludesVat) {
                    // Amount includes VAT - separate it
                    $calculation = MoneyAction::calculateFromTotalIncludingVat($amount, $vatRate);
                    $amount = $calculation['base']; // Store base amount
                    $vatAmount = $calculation['vat'];  // Store VAT amount
                } else {
                    // Amount excludes VAT - calculate VAT on top
                    $vatAmount = MoneyAction::calculateVat($amount, $vatRate);
                    // amount stays as is (base amount)
                }
            }
        }

        $totalAmount = $amount + $vatAmount;

        // For salary payments (crew_member_id present), automatically get/create salary category
        $categoryId = isset($data['category_id']) ? (int) $data['category_id'] : null;
        $crewMemberId = $data['crew_member_id'] ?? null;

        if ($crewMemberId && !$categoryId) {
            // Get or create salary category for this vessel
            $salaryCategory = MovimentationCategory::firstOrCreate(
                [
                    'name'      => 'Salários',
                    'type'      => 'expense',
                    'vessel_id' => $vesselId,
                ],
                [
                    'description' => 'Salary payments to crew members',
                ]
            );
            $categoryId = $salaryCategory->id;
        }

        $transaction = Movimentation::create([
            'vessel_id'        => $vesselId,
            'marea_id'         => $data['marea_id'] ?? null,
            'maintenance_id'   => $data['maintenance_id'] ?? null,
            'category_id'      => $categoryId,
            'type'             => $data['type'],
            'amount'           => $amount,
            'amount_per_unit'  => $data['amount_per_unit'] ?? null,
            'quantity'         => $data['quantity'] ?? null,
            'vat_amount'       => $vatAmount,
            'total_amount'     => $totalAmount,
            'currency'         => $currency,
            'house_of_zeros'   => $data['house_of_zeros'] ?? 2,
            'vat_profile_id'   => $vatProfileId,
            'transaction_date' => $data['transaction_date'],
            'description'      => $data['description'] ?? null,
            'notes'            => $data['notes'] ?? null,
            'supplier_id'      => $data['supplier_id'] ?? null,
            'crew_member_id'   => $crewMemberId,
            'status'           => $data['status'] ?? 'completed',
            'created_by'       => $user->id,
        ]);

        // Handle file uploads if any
        if ($files) {
            if (!is_array($files)) {
                $files = [$files];
            }
            foreach ($files as $file) {
                if (!$file) {
                    continue;
                }
                try {
                    // Save file using TenantFileAction
                    $fileInfo = TenantFileAction::save(
                        vesselId: $vesselId,
                        file: $file,
                        isPublic: false,
                        path: 'transactions',
                        fileName: null,
                        extension: null
                    );

                    // Create transaction file record
                    MovimentationFile::create([
                        'transaction_id' => $transaction->id,
                        'src'            => $fileInfo->url,
                        'name'           => $file->getClientOriginalName(),
                        'size'           => $fileInfo->size,
                        'type'           => $fileInfo->extension,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to upload file for transaction', [
                        'transaction_id' => $transaction->id,
                        'file_name'      => $file->getClientOriginalName(),
                        'error'          => $e->getMessage(),
                    ]);
                    // Continue with other files even if one fails
                }
            }
        }

        // Reload with relationships
        $transaction->load([
            'category',
            'supplier',
            'crewMember',
            'vatProfile',
            'files',
        ]);

        // Log the create action
        AuditLogAction::logCreate(
            $transaction,
            'Transaction',
            $transaction->transaction_number,
            $vesselId
        );

        // Create email notification
        $this->sendNotification($transaction, $user, $vesselId, $currency);

        return $transaction;
    }

    /**
     * Send email notification for the transaction.
     */
    protected function sendNotification(Movimentation $transaction, User $user, int $vesselId, string $currency): void
    {
        try {
            $currencyModel = Currency::where('code', $currency)->first();
            $currencySymbol = $currencyModel->symbol ?? '€';

            EmailNotificationAction::createNotification(
                type: 'transaction_created',
                subjectType: Movimentation::class,
                subjectId: $transaction->id,
                vesselId: $vesselId,
                actionByUserId: $user->id,
                subjectData: [
                    'transaction_number' => $transaction->transaction_number,
                    'type'               => $transaction->type,
                    'amount'             => $transaction->total_amount,
                    'currency_symbol'    => $currencySymbol,
                    'description'        => $transaction->description,
                    'category_name'      => $transaction->category->translated_name ?? null,
                    'created_at'         => $transaction->created_at->toIso8601String(),
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create email notification for transaction', [
                'transaction_id' => $transaction->id,
                'vessel_id'      => $vesselId,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
