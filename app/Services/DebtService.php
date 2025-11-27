<?php

namespace App\Services;

use App\Actions\AuditLogAction;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DebtService
{
    /**
     * Create a new debt.
     */
    public function createDebt(User $user, int $vesselId, array $data): Debt
    {
        return DB::transaction(function () use ($user, $vesselId, $data) {
            $debt = Debt::create([
                'vessel_id'   => $vesselId,
                'supplier_id' => $data['supplier_id'] ?? null,
                'description' => $data['description'],
                'amount'      => $data['amount'],
                'due_date'    => $data['due_date'] ?? null,
                'notes'       => $data['notes'] ?? null,
                'status'      => 'pending',
                'paid_amount' => 0,
            ]);

            AuditLogAction::logCreate($debt, 'Debt', $debt->description, $vesselId);

            return $debt;
        });
    }

    /**
     * Add a payment to a debt.
     */
    public function addPayment(Debt $debt, User $user, array $data): DebtPayment
    {
        return DB::transaction(function () use ($debt, $user, $data) {
            $payment = $debt->payments()->create([
                'amount'       => $data['amount'],
                'payment_date' => $data['payment_date'],
                'notes'        => $data['notes'] ?? null,
            ]);

            // Update debt status and paid amount
            $debt->paid_amount += $payment->amount;
            
            if ($debt->paid_amount >= $debt->amount) {
                $debt->status = 'paid';
            } elseif ($debt->paid_amount > 0) {
                $debt->status = 'partial';
            } else {
                $debt->status = 'pending';
            }
            
            $debt->save();

            AuditLogAction::logCreate($payment, 'Debt Payment', "Payment of {$payment->amount} for {$debt->description}", $debt->vessel_id);
            
            // Log update on debt as well since status/amount changed
            AuditLogAction::logUpdate($debt, [], 'Debt', $debt->description, $debt->vessel_id);

            return $payment;
        });
    }

    /**
     * Update a debt.
     */
    public function updateDebt(Debt $debt, User $user, array $data): Debt
    {
        $originalDebt = $debt->replicate();

        $debt->update([
            'supplier_id' => array_key_exists('supplier_id', $data) ? $data['supplier_id'] : $debt->supplier_id,
            'description' => $data['description'] ?? $debt->description,
            'amount'      => $data['amount'] ?? $debt->amount,
            'due_date'    => $data['due_date'] ?? $debt->due_date,
            'notes'       => $data['notes'] ?? $debt->notes,
        ]);

        // Recalculate status in case amount changed
        if ($debt->paid_amount >= $debt->amount) {
            $debt->status = 'paid';
        } elseif ($debt->paid_amount > 0) {
            $debt->status = 'partial';
        } else {
            $debt->status = 'pending';
        }
        $debt->save();

        $changedFields = AuditLogAction::getChangedFields($debt, $originalDebt);
        AuditLogAction::logUpdate($debt, $changedFields, 'Debt', $debt->description, $debt->vessel_id);

        return $debt;
    }

    /**
     * Delete a debt.
     */
    public function deleteDebt(Debt $debt, User $user): void
    {
        DB::transaction(function () use ($debt, $user) {
            // Log before delete
            AuditLogAction::logDelete($debt, 'Debt', $debt->description, $debt->vessel_id);
            
            $debt->payments()->delete();
            $debt->delete();
        });
    }
}
